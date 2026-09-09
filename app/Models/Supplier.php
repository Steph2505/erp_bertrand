<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'company', 'email', 'phone',
        'address', 'tax_number', 'opening_balance', 'is_active',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'opening_balance' => 'decimal:2',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }
}
