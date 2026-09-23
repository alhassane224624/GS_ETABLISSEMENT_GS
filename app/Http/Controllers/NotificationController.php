<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Affiche la liste des notifications
     */
    public function index(): View
    {
        $notifications = Auth::user()
            ->notifications()
            ->latest()
            ->paginate(20);

        $unreadCount = Auth::user()->unreadNotifications()->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Marque une notification comme lue
     */
    public function markAsRead(string $id): JsonResponse
    {
        $notification = Auth::user()
            ->notifications()
            ->findOrFail($id);

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marquée comme lue'
        ]);
    }

    /**
     * Marque toutes les notifications comme lues
     */
    public function markAllAsRead(): JsonResponse|RedirectResponse
    {
        $updated = Auth::user()
            ->unreadNotifications
            ->markAsRead();

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Toutes les notifications ont été marquées comme lues',
                'updated_count' => $updated
            ]);
        }

        return redirect()
            ->back()
            ->with('success', "Toutes les notifications ont été marquées comme lues");
    }

    /**
     * Supprime une notification (POST au lieu de DELETE pour compatibilité)
     */
    public function destroy(string $id): JsonResponse|RedirectResponse
    {
        $notification = Auth::user()
            ->notifications()
            ->findOrFail($id);
        
        $notification->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Notification supprimée'
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Notification supprimée');
    }

    /**
     * Supprime toutes les notifications lues
     */
    public function deleteRead(): JsonResponse|RedirectResponse
    {
        $deleted = Auth::user()
            ->readNotifications()
            ->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Notifications lues supprimées',
                'deleted_count' => $deleted
            ]);
        }

        return redirect()
            ->back()
            ->with('success', "{$deleted} notification(s) supprimée(s)");
    }

    /**
     * Retourne le nombre de notifications non lues
     */
    public function getUnreadCount(): JsonResponse
    {
        $count = Auth::user()->unreadNotifications()->count();
        
        return response()->json([
            'count' => $count,
            'has_unread' => $count > 0
        ]);
    }

    /**
     * Retourne les notifications récentes
     */
    public function getRecent(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 5);
        
        $notifications = Auth::user()
            ->notifications()
            ->latest()
            ->take($limit)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? 'Notification',
                    'message' => $notification->data['message'] ?? '',
                    'icon' => $notification->data['icon'] ?? 'fas fa-bell',
                    'type' => $notification->data['type'] ?? 'info',
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at->diffForHumans(),
                    'url' => $this->getNotificationUrl($notification)
                ];
            });

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => Auth::user()->unreadNotifications()->count()
        ]);
    }

    /**
     * Détermine l'URL de redirection pour une notification
     */
    private function getNotificationUrl($notification): ?string
    {
        $data = $notification->data;

        // URL explicite dans les données
        if (isset($data['url']) && !empty($data['url'])) {
            return $data['url'];
        }

        // Routes basées sur les IDs
        if (isset($data['stagiaire_id'])) {
            return route('stagiaires.show', $data['stagiaire_id']);
        }

        if (isset($data['bulletin_id'])) {
            return route('bulletins.show', $data['bulletin_id']);
        }

        if (isset($data['paiement_id'])) {
            return route('paiements.show', $data['paiement_id']);
        }

        if (isset($data['message_id']) && isset($data['user_id'])) {
            return route('messages.conversation', $data['user_id']);
        }

        return null;
    }
}