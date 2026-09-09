<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference', 'customer_id', 'warehouse_id', 'pos_session_id', 'sale_date',
        'status', 'payment_status', 'subtotal', 'discount', 'tax_amount',
        'shipping', 'total', 'amount_paid', 'is_pos', 'note', 'created_by',
    ];

    protected $casts = [
        'sale_date'   => 'date',
        'is_pos'      => 'boolean',
        'subtotal'    => 'decimal:2',
        'discount'    => 'decimal:2',
        'tax_amount'  => 'decimal:2',
        'shipping'    => 'decimal:2',
        'total'       => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    public function customer(): BelongsTo    { return $this->belongsTo(Customer::class); }
    public function warehouse(): BelongsTo   { return $this->belongsTo(Warehouse::class); }
    public function posSession(): BelongsTo  { return $this->belongsTo(PosSession::class); }
    public function createdBy(): BelongsTo   { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany        { return $this->hasMany(SaleItem::class); }
    public function returns(): HasMany      { return $this->hasMany(SaleReturn::class); }
    public function payments(): MorphMany   { return $this->morphMany(Payment::class, 'payable'); }

    public function getAmountDueAttribute(): float
    {
        return (float) ($this->total - $this->amount_paid);
    }
}
