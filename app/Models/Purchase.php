<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference', 'supplier_id', 'warehouse_id', 'purchase_date',
        'status', 'payment_status', 'subtotal', 'discount', 'tax_amount',
        'shipping', 'total', 'amount_paid', 'note', 'attachment', 'created_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'subtotal'      => 'decimal:2',
        'discount'      => 'decimal:2',
        'tax_amount'    => 'decimal:2',
        'shipping'      => 'decimal:2',
        'total'         => 'decimal:2',
        'amount_paid'   => 'decimal:2',
    ];

    public function supplier(): BelongsTo   { return $this->belongsTo(Supplier::class); }
    public function warehouse(): BelongsTo  { return $this->belongsTo(Warehouse::class); }
    public function createdBy(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany        { return $this->hasMany(PurchaseItem::class); }
    public function returns(): HasMany      { return $this->hasMany(PurchaseReturn::class); }
    public function payments(): MorphMany   { return $this->morphMany(Payment::class, 'payable'); }

    public function getAmountDueAttribute(): float
    {
        return (float) ($this->total - $this->amount_paid);
    }
}
