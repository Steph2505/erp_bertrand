<?php

namespace App\Notifications;

use App\Helpers\FormatHelper;
use App\Models\Purchase;
use Illuminate\Notifications\Notification;

class SupplierDebtOverdue extends Notification
{
    public function __construct(
        private readonly Purchase $purchase
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $purchase = $this->purchase;
        $supplier = $purchase->supplier?->name ?? 'Fournisseur inconnu';

        return [
            'purchase_id'            => $purchase->id,
            'reference'              => $purchase->reference,
            'supplier'               => $supplier,
            'amount_due'             => (float) $purchase->amount_due,
            'expected_payment_date'  => optional($purchase->expected_payment_date)->format('Y-m-d'),
            'message'                => "Dette envers « {$supplier} » : " . FormatHelper::money((float) $purchase->amount_due) . " ({$purchase->reference}) — échéance de paiement dépassée.",
            'url'                    => route('purchases.show', $purchase->id),
        ];
    }
}
