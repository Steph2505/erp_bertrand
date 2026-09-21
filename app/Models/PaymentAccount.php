<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentAccount extends Model
{
    protected $fillable = [
        'name', 'type', 'opening_balance', 'current_balance',
        'account_number', 'is_active', 'is_default',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'is_default'      => 'boolean',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(AccountTransfer::class, 'from_account_id');
    }

    public function transfersTo(): HasMany
    {
        return $this->hasMany(AccountTransfer::class, 'to_account_id');
    }
}
