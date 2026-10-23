<?php

namespace App\Notifications;

use App\Models\Publication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PublicationCreated extends Notification
{
    use Queueable;

    public function __construct(public Publication $publication)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $p = $this->publication;
        $titres = [
            'devoir' => 'Nouveau devoir',
            'document' => 'Nouveau document',
            'resultats' => 'Résultats publiés',
        ];

        $message = ($p->matiere->nom ?? 'Matière') . ' : ' . $p->titre;
        if ($p->estDevoir() && $p->date_limite) {
            $message .= ' — à rendre pour le ' . $p->date_limite->isoFormat('D MMMM [à] HH[h]mm');
        }

        return [
            'title' => $titres[$p->type] ?? 'Nouvelle publication',
            'message' => $message,
            'publication_id' => $p->id,
            'url' => $p->type === 'resultats' ? route('stagiaire.notes') : route('stagiaire.publications.show', $p),
            'icon' => 'fas ' . $p->icone,
            'type' => $p->type === 'devoir' ? 'warning' : 'info',
        ];
    }
}
