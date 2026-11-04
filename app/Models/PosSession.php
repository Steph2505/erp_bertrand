<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSession extends Model
{
    protected $fillable = [
        'caisse_id', 'warehouse_id', 'user_id', 'opening_balance', 'closing_balance',
        'expected_closing_balance', 'total_sales', 'opened_at', 'closed_at', 'note',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'expected_closing_balance' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }

    public function getSalesCountAttribute(): int
    {
        return $this->sales()->count();
    }

    public function getExpectedClosingAttribute(): float
    {
        if ($this->expected_closing_balance !== null) {
            return (float) $this->expected_closing_balance;
        }

        if ($this->closed_at !== null) {
            return (float) ($this->closing_balance ?? ($this->opening_balance + $this->total_sales));
        }

        return (float) ($this->opening_balance + $this->cash_sales - $this->cash_refunds);
    }

    public function getCashSalesAttribute(): float
    {
        return (float) Payment::where('payable_type', Sale::class)
            ->where('payment_method', 'cash')
            ->whereIn('payable_id', $this->sales()->select('id'))
            ->where('created_at', '>=', $this->opened_at)
            ->when($this->closed_at, fn ($query) => $query->where('created_at', '<=', $this->closed_at))
            ->sum('amount');
    }

    public function getCashRefundsAttribute(): float
    {
        return (float) Payment::where('payable_type', SaleReturn::class)
            ->where('payment_method', 'cash')
            ->whereIn('payable_id', SaleReturn::select('id')->where('pos_session_id', $this->id))
            ->where('created_at', '>=', $this->opened_at)
            ->when($this->closed_at, fn ($query) => $query->where('created_at', '<=', $this->closed_at))
            ->sum('amount');
    }
}
