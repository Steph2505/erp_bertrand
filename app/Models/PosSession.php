<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSession extends Model
{
    protected $fillable = [
        'caisse_id', 'warehouse_id', 'user_id', 'opening_balance', 'closing_balance',
        'total_sales', 'opened_at', 'closed_at', 'note',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'total_sales'     => 'decimal:2',
        'opened_at'       => 'datetime',
        'closed_at'       => 'datetime',
    ];

    public function caisse(): BelongsTo    { return $this->belongsTo(Caisse::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function user(): BelongsTo      { return $this->belongsTo(User::class); }
    public function sales(): HasMany       { return $this->hasMany(Sale::class); }

    public function isOpen(): bool  { return $this->closed_at === null; }

    public function getSalesCountAttribute(): int
    {
        return $this->sales()->count();
    }

    public function getExpectedClosingAttribute(): float
    {
        return (float) ($this->opening_balance + $this->total_sales);
    }
}
