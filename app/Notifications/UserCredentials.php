<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCredentials extends Notification
{
    public function __construct(
        private readonly string $plainPassword
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $appName = config('app.name', 'ERP Espace Mokolo d\'Obala');
        $loginUrl = route('login');

        return (new MailMessage)
            ->subject("Vos identifiants de connexion — {$appName}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Un compte vous a été créé sur **{$appName}**.")
            ->line("Voici vos identifiants de connexion :")
            ->line("**Email :** {$notifiable->email}")
            ->line("**Mot de passe :** {$this->plainPassword}")
            ->action('Se connecter', $loginUrl)
            ->line("Pour des raisons de sécurité, nous vous recommandons de changer votre mot de passe dès votre première connexion.")
            ->salutation("L'équipe {$appName}");
    }
}
