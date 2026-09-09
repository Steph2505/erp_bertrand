<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Pack extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $fillable = ['name', 'description', 'image', 'barcode', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function items(): HasMany
    {
        return $this->hasMany(PackItem::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(PackPrice::class);
    }

    public function defaultPrice(): HasOne
    {
        return $this->hasOne(PackPrice::class)->whereNull('price_group_id');
    }

    public function getDefaultBuyingPriceAttribute(): float
    {
        return (float) ($this->defaultPrice?->buying_price ?? 0);
    }

    public function getDefaultSellingPriceAttribute(): float
    {
        return (float) ($this->defaultPrice?->selling_price ?? 0);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }
}
