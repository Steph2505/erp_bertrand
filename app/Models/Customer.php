<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'address', 'tax_number',
        'customer_group_id', 'price_group_id', 'opening_balance', 'is_active',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'opening_balance' => 'decimal:2',
    ];

    public function group(): BelongsTo    { return $this->belongsTo(CustomerGroup::class, 'customer_group_id'); }
    public function priceGroup(): BelongsTo { return $this->belongsTo(PriceGroup::class); }
    public function sales(): HasMany      { return $this->hasMany(Sale::class); }
}
