<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackPrice extends Model
{
    protected $fillable = ['pack_id', 'price_group_id', 'buying_price', 'selling_price'];

    protected $casts = [
        'buying_price'  => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class);
    }

    public function priceGroup(): BelongsTo
    {
        return $this->belongsTo(PriceGroup::class);
    }

    public function getMarginPercentAttribute(): float
    {
        if ($this->buying_price <= 0) {
            return 0;
        }
        return round((($this->selling_price - $this->buying_price) / $this->buying_price) * 100, 2);
    }
}
