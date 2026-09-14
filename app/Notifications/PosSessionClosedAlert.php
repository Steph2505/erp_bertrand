<?php

namespace App\Notifications;

use App\Models\PosSession;
use Illuminate\Notifications\Notification;

class PosSessionClosedAlert extends Notification
{
    public function __construct(
        private readonly PosSession $session
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $session   = $this->session;
        $caisse    = $session->caisse?->name ?? 'Caisse';
        $cashier   = $session->user?->name ?? 'Utilisateur';
        $gap       = round((float) $session->closing_balance - $session->expected_closing, 2);

        $message = "Caisse clôturée : « {$caisse} » par {$cashier}.";
        if (abs($gap) >= 0.01) {
            $sign     = $gap > 0 ? '+' : '';
            $message .= " Écart : {$sign}" . number_format($gap, 0, ',', ' ') . '.';
        }

        return [
            'session_id'       => $session->id,
            'caisse'           => $caisse,
            'cashier'          => $cashier,
            'closing_balance'  => (float) $session->closing_balance,
            'expected_closing' => $session->expected_closing,
            'gap'              => $gap,
            'message'          => $message,
            'url'              => route('pos.sessions.show', $session->id),
        ];
    }
}
