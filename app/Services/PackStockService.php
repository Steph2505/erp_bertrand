<?php

namespace App\Services;

use App\Models\Pack;
use App\Models\ProductStock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class PackStockService
{
    public function calculateUnitsForSale(int $packId, int $quantity): array
    {
        $pack = Pack::with('items.product')->findOrFail($packId);

        return $pack->items->map(fn($item) => [
            'product_id'   => $item->product_id,
            'product_name' => $item->product->name,
            'units'        => $item->quantity * $quantity,
        ])->toArray();
    }

    public function hasEnoughStock(int $packId, int $quantity, ?int $warehouseId = null): bool
    {
        $pack = Pack::with('items.product')->findOrFail($packId);

        foreach ($pack->items as $item) {
            $needed = $item->quantity * $quantity;

            $available = $warehouseId
                ? ((int) ProductStock::where('product_id', $item->product_id)
                    ->where('warehouse_id', $warehouseId)
                    ->value('quantity') ?? 0)
                : $item->product->stock_quantity;

            if ($available < $needed) {
                return false;
            }
        }

        return true;
    }

    public function availablePackCount(int $packId, ?int $warehouseId = null): int
    {
        $pack = Pack::with('items.product')->findOrFail($packId);

        if ($pack->items->isEmpty()) {
            return 0;
        }

        return (int) $pack->items->min(function ($item) use ($warehouseId) {
            $available = $warehouseId
                ? ((int) ProductStock::where('product_id', $item->product_id)
                    ->where('warehouse_id', $warehouseId)
                    ->value('quantity') ?? 0)
                : $item->product->stock_quantity;

            return floor($available / $item->quantity);
        });
    }

    public function deductStockForSale(int $packId, int $quantity, ?string $reference = null, ?int $warehouseId = null): void
    {
        DB::transaction(function () use ($packId, $quantity, $reference, $warehouseId) {
            $pack = Pack::with('items.product')->findOrFail($packId);

            foreach ($pack->items as $item) {
                $unitsToDeduct = $item->quantity * $quantity;
                $this->applyMovement(
                    $item->product, -$unitsToDeduct, 'sale', $reference, $packId,
                    "Vente de {$quantity} pack(s) « {$pack->name} »", $warehouseId
                );
            }
        });
    }

    public function restoreStockForReturn(int $packId, int $quantity, ?string $reference = null, ?int $warehouseId = null): void
    {
        DB::transaction(function () use ($packId, $quantity, $reference, $warehouseId) {
            $pack = Pack::with('items.product')->findOrFail($packId);

            foreach ($pack->items as $item) {
                $unitsToRestore = $item->quantity * $quantity;
                $this->applyMovement(
                    $item->product, $unitsToRestore, 'return_sale', $reference, $packId,
                    "Retour de {$quantity} pack(s) « {$pack->name} »", $warehouseId
                );
            }
        });
    }

    public function addStockForPurchase(int $packId, int $quantity, ?string $reference = null, ?int $warehouseId = null): void
    {
        DB::transaction(function () use ($packId, $quantity, $reference, $warehouseId) {
            $pack = Pack::with('items.product')->findOrFail($packId);

            foreach ($pack->items as $item) {
                $unitsToAdd = $item->quantity * $quantity;
                $this->applyMovement(
                    $item->product, $unitsToAdd, 'purchase', $reference, $packId,
                    "Achat de {$quantity} pack(s) « {$pack->name} »", $warehouseId
                );
            }
        });
    }

    /**
     * Applique un delta de stock (global + magasin) et journalise le
     * mouvement avec un snapshot avant/après du périmètre concerné (stock du
     * magasin si $warehouseId est fourni, sinon stock global du produit).
     */
    private function applyMovement(
        \App\Models\Product $product,
        int $delta,
        string $type,
        ?string $reference,
        int $packId,
        string $note,
        ?int $warehouseId
    ): void {
        if ($warehouseId) {
            $quantityBefore = ProductStock::firstOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $warehouseId],
                ['quantity' => 0, 'min_quantity' => 0]
            )->quantity;
            $stock         = ProductStock::adjust($product->id, $warehouseId, $delta);
            $quantityAfter = $stock->quantity;
            $product->increment('stock_quantity', $delta);
        } else {
            $quantityBefore = max(0, (int) $product->stock_quantity);
            $product->increment('stock_quantity', $delta);
            $quantityAfter = $quantityBefore + $delta;
        }

        StockMovement::create([
            'product_id'      => $product->id,
            'warehouse_id'    => $warehouseId,
            'quantity'        => $delta,
            'quantity_before' => $quantityBefore,
            'quantity_after'  => $quantityAfter,
            'type'            => $type,
            'reference'       => $reference,
            'pack_id'         => $packId,
            'note'            => $note,
            'created_by'      => auth()->id(),
        ]);
    }
}
