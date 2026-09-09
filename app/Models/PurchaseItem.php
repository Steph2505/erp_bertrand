<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id', 'product_id', 'pack_id', 'item_type', 'item_name',
        'quantity', 'units_per_item', 'unit_price', 'discount', 'tax_rate', 'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'discount'   => 'decimal:2',
        'tax_rate'   => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    public function purchase(): BelongsTo { return $this->belongsTo(Purchase::class); }
    public function product(): BelongsTo  { return $this->belongsTo(Product::class); }
    public function pack(): BelongsTo     { return $this->belongsTo(Pack::class); }

    public function getTotalUnitsAttribute(): int
    {
        return $this->quantity * $this->units_per_item;
    }
}
