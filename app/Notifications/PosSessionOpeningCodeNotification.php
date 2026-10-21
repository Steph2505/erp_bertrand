<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PosSessionOpeningCodeNotification extends Notification
{
    public function __construct(
        private readonly string $code,
        private readonly string $caisseName,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Code de vérification pour ouvrir la caisse')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Vous demandez à ouvrir une session sur la caisse « {$this->caisseName} ».")
            ->line("Votre code à usage unique : **{$this->code}**")
            ->line('Ce code expire dans 10 minutes et ne peut être utilisé qu’une seule fois.')
            ->line('Si vous n’êtes pas à l’origine de cette demande, ignorez ce message.');
    }
}