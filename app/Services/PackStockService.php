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

                $item->product->decrement('stock_quantity', $unitsToDeduct);

                if ($warehouseId) {
                    ProductStock::adjust($item->product_id, $warehouseId, -$unitsToDeduct);
                }

                StockMovement::create([
                    'product_id'   => $item->product_id,
                    'warehouse_id' => $warehouseId,
                    'quantity'     => -$unitsToDeduct,
                    'type'         => 'sale',
                    'reference'    => $reference,
                    'pack_id'      => $packId,
                    'note'         => "Vente de {$quantity} pack(s) « {$pack->name} »",
                    'created_by'   => auth()->id(),
                ]);
            }
        });
    }

    public function restoreStockForReturn(int $packId, int $quantity, ?string $reference = null, ?int $warehouseId = null): void
    {
        DB::transaction(function () use ($packId, $quantity, $reference, $warehouseId) {
            $pack = Pack::with('items.product')->findOrFail($packId);

            foreach ($pack->items as $item) {
                $unitsToRestore = $item->quantity * $quantity;

                $item->product->increment('stock_quantity', $unitsToRestore);

                if ($warehouseId) {
                    ProductStock::adjust($item->product_id, $warehouseId, $unitsToRestore);
                }

                StockMovement::create([
                    'product_id'   => $item->product_id,
                    'warehouse_id' => $warehouseId,
                    'quantity'     => $unitsToRestore,
                    'type'         => 'return_sale',
                    'reference'    => $reference,
                    'pack_id'      => $packId,
                    'note'         => "Retour de {$quantity} pack(s) « {$pack->name} »",
                    'created_by'   => auth()->id(),
                ]);
            }
        });
    }

    public function addStockForPurchase(int $packId, int $quantity, ?string $reference = null, ?int $warehouseId = null): void
    {
        DB::transaction(function () use ($packId, $quantity, $reference, $warehouseId) {
            $pack = Pack::with('items.product')->findOrFail($packId);

            foreach ($pack->items as $item) {
                $unitsToAdd = $item->quantity * $quantity;

                $item->product->increment('stock_quantity', $unitsToAdd);

                if ($warehouseId) {
                    ProductStock::adjust($item->product_id, $warehouseId, $unitsToAdd);
                }

                StockMovement::create([
                    'product_id'   => $item->product_id,
                    'warehouse_id' => $warehouseId,
                    'quantity'     => $unitsToAdd,
                    'type'         => 'purchase',
                    'reference'    => $reference,
                    'pack_id'      => $packId,
                    'note'         => "Achat de {$quantity} pack(s) « {$pack->name} »",
                    'created_by'   => auth()->id(),
                ]);
            }
        });
    }
}
