<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerGroup extends Model
{
    protected $fillable = ['name', 'discount', 'is_wholesale'];

    protected $casts = ['discount' => 'decimal:2', 'is_wholesale' => 'boolean'];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
