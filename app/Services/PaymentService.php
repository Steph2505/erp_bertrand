<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentAccount;

class PaymentService
{
    /**
     * Résout le compte à utiliser :
     * 1. Compte explicitement fourni
     * 2. Premier compte actif dont le type correspond au mode de paiement
     * 3. Compte par défaut
     * 4. Premier compte actif (fallback ultime)
     */
    public function resolveAccount(?int $accountId, ?string $method = null): ?PaymentAccount
    {
        if ($accountId) {
            return PaymentAccount::find($accountId);
        }

        $accountType = $this->methodToAccountType($method);

        if ($accountType) {
            $match = PaymentAccount::where('type', $accountType)
                ->where('is_active', true)
                ->first();
            if ($match) {
                return $match;
            }
        }

        return PaymentAccount::where('is_default', true)->first()
            ?? PaymentAccount::where('is_active', true)->first();
    }

    /**
     * Convertit un mode de paiement en type de compte.
     */
    private function methodToAccountType(?string $method): ?string
    {
        return match($method) {
            'mobile_money'                    => 'mobile_money',
            'bank_transfer', 'check', 'card'  => 'bank',
            'cash'                            => 'cash',
            default                           => null,
        };
    }

    /**
     * Enregistre un paiement entrant (vente) et crédite le compte.
     */
    public function recordInflow(
        object $payable,
        float $amount,
        string $method,
        ?int $accountId = null,
        ?string $date = null
    ): Payment {
        $account = $this->resolveAccount($accountId, $method);

        $payment = Payment::create([
            'reference'          => $this->nextReference(),
            'payable_type'       => get_class($payable),
            'payable_id'         => $payable->id,
            'payment_account_id' => $account?->id,
            'amount'             => $amount,
            'payment_date'       => $date ?? now()->toDateString(),
            'payment_method'     => $method,
            'created_by'         => auth()->id(),
        ]);

        $account?->increment('current_balance', $amount);

        return $payment;
    }

    /**
     * Enregistre un paiement sortant (achat) et débite le compte.
     */
    public function recordOutflow(
        object $payable,
        float $amount,
        string $method,
        ?int $accountId = null,
        ?string $date = null
    ): Payment {
        $account = $this->resolveAccount($accountId, $method);

        $payment = Payment::create([
            'reference'          => $this->nextReference(),
            'payable_type'       => get_class($payable),
            'payable_id'         => $payable->id,
            'payment_account_id' => $account?->id,
            'amount'             => $amount,
            'payment_date'       => $date ?? now()->toDateString(),
            'payment_method'     => $method,
            'created_by'         => auth()->id(),
        ]);

        $account?->decrement('current_balance', $amount);

        return $payment;
    }

    private function nextReference(): string
    {
        return 'PAY-' . date('Ymd') . '-' . str_pad(Payment::count() + 1, 5, '0', STR_PAD_LEFT);
    }
}
