<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = ['name', 'code', 'address', 'phone', 'email', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
