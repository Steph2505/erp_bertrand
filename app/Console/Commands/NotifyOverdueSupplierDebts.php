<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use App\Models\User;
use App\Notifications\SupplierDebtOverdue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class NotifyOverdueSupplierDebts extends Command
{
    protected $signature = 'purchases:notify-overdue-debts';
    protected $description = 'Notifie les utilisateurs des achats partiellement payés dont l\'échéance de solde est dépassée';

    public function handle(): int
    {
        $overdue = Purchase::with('supplier')
            ->where('payment_status', 'partial')
            ->whereNotNull('expected_payment_date')
            ->whereDate('expected_payment_date', '<', now()->toDateString())
            ->whereNull('overdue_notified_at')
            ->get();

        if ($overdue->isEmpty()) {
            $this->info('Aucune dette fournisseur en retard.');
            return self::SUCCESS;
        }

        $recipients = User::permission('receive notifications')->where('is_active', true)->get();

        if ($recipients->isEmpty()) {
            $this->warn('Aucun destinataire de notifications actif.');
            return self::SUCCESS;
        }

        foreach ($overdue as $purchase) {
            Notification::send($recipients, new SupplierDebtOverdue($purchase));
            $purchase->update(['overdue_notified_at' => now()]);
        }

        $this->info("{$overdue->count()} dette(s) fournisseur en retard notifiée(s).");
        return self::SUCCESS;
    }
}
