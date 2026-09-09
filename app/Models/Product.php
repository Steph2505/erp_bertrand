<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'name', 'variation', 'slug', 'description', 'barcode',
        'category_id', 'brand_id', 'unit_id',
        'buying_price', 'selling_price', 'tax_rate',
        'stock_quantity', 'min_stock_quantity',
        'expiry_date', 'can_be_packed', 'pack_quantity', 'pack_price', 'is_active', 'has_variations',
    ];

    protected $casts = [
        'can_be_packed'    => 'boolean',
        'is_active'        => 'boolean',
        'has_variations'   => 'boolean',
        'buying_price'     => 'decimal:2',
        'selling_price'    => 'decimal:2',
        'pack_price'       => 'decimal:2',
        'tax_rate'         => 'decimal:2',
        'expiry_date'      => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn($m) => $m->slug ??= Str::slug($m->name));
    }

    public function category(): BelongsTo    { return $this->belongsTo(Category::class); }
    public function brand(): BelongsTo       { return $this->belongsTo(Brand::class); }
    public function unit(): BelongsTo        { return $this->belongsTo(Unit::class); }
    public function variations(): HasMany    { return $this->hasMany(ProductVariation::class); }
    public function packItems(): HasMany     { return $this->hasMany(PackItem::class); }
    public function stockMovements(): HasMany { return $this->hasMany(StockMovement::class); }
    public function stocks(): HasMany        { return $this->hasMany(ProductStock::class); }

    public function stockInWarehouse(int $warehouseId): int
    {
        return (int) ($this->stocks()->where('warehouse_id', $warehouseId)->value('quantity') ?? 0);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->variation ? "{$this->name} - {$this->variation}" : $this->name;
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->min_stock_quantity;
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->expiry_date && $this->expiry_date->diffInDays(now()) <= $days;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }
}
