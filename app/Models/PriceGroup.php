<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceGroup extends Model
{
    protected $fillable = ['name', 'description'];

    public function packPrices(): HasMany
    {
        return $this->hasMany(PackPrice::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
