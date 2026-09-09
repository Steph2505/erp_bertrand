<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStock extends Model
{
    protected $fillable = ['product_id', 'warehouse_id', 'quantity', 'min_quantity'];

    public function product(): BelongsTo   { return $this->belongsTo(Product::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }

    /**
     * Ajuste la quantité dans ce magasin, crée l'entrée si elle n'existe pas.
     */
    public static function adjust(int $productId, int $warehouseId, int $delta): self
    {
        $stock = self::firstOrCreate(
            ['product_id' => $productId, 'warehouse_id' => $warehouseId],
            ['quantity' => 0, 'min_quantity' => 0]
        );

        $stock->increment('quantity', $delta);
        return $stock->fresh();
    }
}
