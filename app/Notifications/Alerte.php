<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification simple (titre, message, lien), enregistrée dans l'application
 * et envoyée par e-mail si $parMail est vrai.
 */
class Alerte extends Notification
{
    use Queueable;

    public function __construct(
        public string $titre,
        public string $message,
        public ?string $url = null,
        public string $icone = 'fas fa-bell',
        public string $niveau = 'info', // info, success, warning, danger
        public bool $parMail = false,
    ) {
    }

    public function via(object $notifiable): array
    {
        return $this->parMail ? ['database', 'mail'] : ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->titre,
            'message' => $this->message,
            'url' => $this->url,
            'icon' => $this->icone,
            'type' => $this->niveau,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->titre . ' — ' . \App\Support\Etablissement::get('nom'))
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line($this->message);

        if ($this->url) {
            $mail->action('Ouvrir dans l\'application', $this->url);
        }

        return $mail->salutation('L\'administration — ' . \App\Support\Etablissement::get('nom'));
    }
}
