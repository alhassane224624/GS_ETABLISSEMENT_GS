<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Models\Classe;
use App\Models\Filiere;
use App\Models\Stagiaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

// 🔔 AJOUT : Importer la notification
use App\Notifications\MessageReceived;

class MessageController extends Controller
{
    private function layout(): string
    {
        $user = Auth::user();
        if (!$user) return 'layouts.app';

        return match ($user->role) {
            'administrateur' => 'layouts.app',
            'comptable'  => 'layouts.comptable',
            'professeur' => 'layouts.app-professeur',
            'stagiaire'  => 'layouts.app-stagiaire',
            default      => 'layouts.app',
        };
    }

    public function index()
    {
        $userId = Auth::id();

        $lastPerContact = DB::table('messages as m1')
            ->selectRaw('LEAST(m1.sender_id, m1.receiver_id) as u1, GREATEST(m1.sender_id, m1.receiver_id) as u2, MAX(m1.id) as last_id')
            ->where(function ($q) use ($userId) {
                $q->where('m1.sender_id', $userId)->orWhere('m1.receiver_id', $userId);
            })
            ->groupBy('u1', 'u2');

        $lastMessages = DB::table('messages as m')
            ->joinSub($lastPerContact, 't', fn($join) => $join->on('m.id', '=', 't.last_id'))
            ->select('m.*', DB::raw("CASE WHEN m.sender_id = $userId THEN m.receiver_id ELSE m.sender_id END as contact_id"))
            ->orderByDesc('m.created_at')
            ->get();

        $contacts = User::whereIn('id', $lastMessages->pluck('contact_id'))->get()->keyBy('id');

        $unread = DB::table('messages')
            ->selectRaw('sender_id as contact_id, COUNT(*) as count')
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->groupBy('sender_id')
            ->pluck('count', 'contact_id');

        $threads = $lastMessages->map(function ($msg) use ($contacts, $unread) {
            $contact = $contacts[$msg->contact_id] ?? null;
            return [
                'contact'      => $contact,
                'last_message' => $msg,
                'unread_count' => $unread[$msg->contact_id] ?? 0,
            ];
        })->filter(fn($t) => $t['contact'] !== null);

        $layout = $this->layout();
        return view('messages.index', compact('threads', 'layout'));
    }

    public function conversation(User $user)
    {
        $authId = Auth::id();

        Message::where('sender_id', $user->id)
            ->where('receiver_id', $authId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = Message::where(function ($q) use ($authId, $user) {
                $q->where('sender_id', $authId)->where('receiver_id', $user->id);
            })
            ->orWhere(function ($q) use ($authId, $user) {
                $q->where('sender_id', $user->id)->where('receiver_id', $authId);
            })
            ->orderBy('created_at')
            ->get();

        $layout = $this->layout();
        return view('messages.conversation', [
            'messages' => $messages,
            'contact'  => $user,
            'layout'   => $layout,
        ]);
    }

    public function store(Request $request, User $user)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $message = Message::create([
            'sender_id'   => Auth::id(),
            'receiver_id' => $user->id,
            'message'     => $validated['message'],
        ]);

        // 🔔 NOTIFICATION : Notifier le destinataire du nouveau message
        $user->notify(new MessageReceived($message));

        return redirect()
            ->route('messages.conversation', $user->id)
            ->with('success', 'Message envoyé avec succès.');
    }

    public function create()
    {
        $users = User::where('id', '!=', Auth::id())
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        $layout = $this->layout();
        return view('messages.create', compact('users', 'layout'));
    }

    public function sendById(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'message'     => ['required', 'string', 'max:1000'],
        ]);

        if ((int) $validated['receiver_id'] === (int) Auth::id()) {
            return back()->with('error', 'Vous ne pouvez pas vous envoyer un message.');
        }

        $receiver = User::findOrFail($validated['receiver_id']);

        $message = Message::create([
            'sender_id'   => Auth::id(),
            'receiver_id' => $receiver->id,
            'message'     => $validated['message'],
        ]);

        // 🔔 NOTIFICATION : Notifier le destinataire
        $receiver->notify(new MessageReceived($message));

        return redirect()
            ->route('messages.conversation', $receiver->id)
            ->with('success', 'Message envoyé à ' . $receiver->name);
    }

    /**
     * Message de groupe : administrateur (tous les stagiaires) ou professeur (ses filières uniquement).
     */
    private function filieresAutorisees(): ?\Illuminate\Support\Collection
    {
        $user = Auth::user();
        if ($user->role === 'administrateur') {
            return null; // pas de restriction
        }
        abort_unless($user->role === 'professeur', 403, 'Seuls les administrateurs et les professeurs peuvent écrire à un groupe.');

        return $user->filieres()->pluck('filieres.id');
    }

    public function showSendGroupForm()
    {
        $autorisees = $this->filieresAutorisees();

        $filieres = Filiere::select('id', 'nom')
            ->when($autorisees !== null, fn ($q) => $q->whereIn('id', $autorisees))
            ->orderBy('nom')->get();
        $classes = Classe::with('filiere:id,nom')->select('id', 'nom', 'filiere_id')
            ->when($autorisees !== null, fn ($q) => $q->whereIn('filiere_id', $autorisees))
            ->orderBy('nom')->get();
        $layout = $this->layout();

        return view('messages.send-group', compact('filieres', 'classes', 'layout'));
    }

    public function sendGroup(Request $request)
    {
        $autorisees = $this->filieresAutorisees();

        $validated = $request->validate([
            'filiere_id' => 'nullable|exists:filieres,id',
            'classe_id'  => 'nullable|exists:classes,id',
            'message'    => 'required|string|max:1000',
        ]);

        // Un professeur ne cible que ses filières
        if ($autorisees !== null) {
            if (!empty($validated['filiere_id']) && !$autorisees->contains((int) $validated['filiere_id'])) {
                abort(403, 'Cette filière ne vous est pas affectée.');
            }
            if (!empty($validated['classe_id']) && !$autorisees->contains((int) Classe::whereKey($validated['classe_id'])->value('filiere_id'))) {
                abort(403, 'Cette classe ne vous est pas affectée.');
            }
        }

        $stagiaires = Stagiaire::query()
            ->where('statut', 'actif')
            ->when($autorisees !== null, fn ($q) => $q->whereIn('filiere_id', $autorisees))
            ->when(!empty($validated['filiere_id']), fn ($q) => $q->where('filiere_id', $validated['filiere_id']))
            ->when(!empty($validated['classe_id']), fn ($q) => $q->where('classe_id', $validated['classe_id']))
            ->with('user')
            ->get();

        $receivers = $stagiaires->map(fn ($s) => $s->user)->filter()->unique('id');

        if ($receivers->isEmpty()) {
            return back()->withInput()->with('error', 'Aucun stagiaire actif ne correspond à ce groupe.');
        }

        DB::transaction(function () use ($receivers, $validated) {
            foreach ($receivers as $receiver) {
                $message = Message::create([
                    'sender_id'   => Auth::id(),
                    'receiver_id' => $receiver->id,
                    'message'     => $validated['message'],
                ]);
                $receiver->notify(new MessageReceived($message));
            }
        });

        return redirect()->route('messages.index')
            ->with('success', 'Message envoyé à ' . $receivers->count() . ' stagiaire(s).');
    }

    public function deleteConversation(User $user)
    {
        $auth = Auth::user();
        
        // 🔒 VÉRIFICATION : Seuls les administrateurs peuvent supprimer
        if ($auth->role !== 'administrateur') {
            abort(403, 'Action non autorisée. Seuls les administrateurs peuvent supprimer des conversations.');
        }

        DB::table('messages')
            ->where(function ($q) use ($auth, $user) {
                $q->where('sender_id', $auth->id)->where('receiver_id', $user->id);
            })
            ->orWhere(function ($q) use ($auth, $user) {
                $q->where('sender_id', $user->id)->where('receiver_id', $auth->id);
            })
            ->delete();

        return redirect()->route('messages.index')
            ->with('success', 'Conversation supprimée avec succès.');
    }

    public function bulkDelete(Request $request)
    {
        $auth = Auth::user();
        
        // 🔒 VÉRIFICATION : Seuls les administrateurs peuvent supprimer en masse
        if ($auth->role !== 'administrateur') {
            abort(403, 'Action non autorisée. Seuls les administrateurs peuvent supprimer des conversations.');
        }

        $validated = $request->validate([
            'selected_conversations' => 'required|array',
            'selected_conversations.*' => 'integer|exists:users,id',
        ]);

        DB::transaction(function () use ($validated, $auth) {
            foreach ($validated['selected_conversations'] as $userId) {
                DB::table('messages')
                    ->where(function ($q) use ($auth, $userId) {
                        $q->where('sender_id', $auth->id)->where('receiver_id', $userId);
                    })
                    ->orWhere(function ($q) use ($auth, $userId) {
                        $q->where('sender_id', $userId)->where('receiver_id', $auth->id);
                    })
                    ->delete();
            }
        });

        return redirect()->route('messages.index')
            ->with('success', 'Les conversations sélectionnées ont été supprimées avec succès.');
    }

    // 🔒 NOUVELLE MÉTHODE : Supprimer toutes les conversations (Admin uniquement)
    public function reset()
    {
        $auth = Auth::user();
        
        // 🔒 VÉRIFICATION : Seuls les administrateurs peuvent tout supprimer
        if ($auth->role !== 'administrateur') {
            abort(403, 'Action non autorisée. Seuls les administrateurs peuvent supprimer toutes les conversations.');
        }

        // Supprimer tous les messages de l'utilisateur connecté
        DB::table('messages')
            ->where('sender_id', $auth->id)
            ->orWhere('receiver_id', $auth->id)
            ->delete();

        return redirect()->route('messages.index')
            ->with('success', '✅ Toutes vos conversations ont été supprimées avec succès.');
    }

    public function unreadCount()
    {
        $count = Message::where('receiver_id', Auth::id())
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }
}