<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariation extends Model
{
    protected $fillable = [
        'product_id', 'name', 'sku', 'barcode',
        'buying_price', 'selling_price', 'stock_quantity', 'is_active',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'buying_price'  => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
