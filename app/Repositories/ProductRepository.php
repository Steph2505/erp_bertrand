<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductRepository
{
    public function paginate(array $filters = [], int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        return Product::with(['category', 'unit', 'brand'])
            ->when($filters['search'] ?? null, fn($q, $s) =>
                $q->where('name', 'like', "%$s%")->orWhere('barcode', 'like', "%$s%")
            )
            ->when($filters['category_id'] ?? null, fn($q, $id) => $q->where('category_id', $id))
            ->when($filters['brand_id'] ?? null, fn($q, $id) => $q->where('brand_id', $id))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', $filters['is_active']))
            ->when($filters['can_be_packed'] ?? null, fn($q) => $q->where('can_be_packed', true))
            ->when($filters['low_stock'] ?? null, fn($q) => $q->whereRaw('stock_quantity <= min_stock_quantity'))
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function find(int $id): Product
    {
        return Product::with(['category', 'unit', 'brand', 'variations', 'stocks.warehouse'])->findOrFail($id);
    }

    public function create(array $data): Product
    {
        try {
            return Product::create($data);
        } catch (Throwable $e) {
            Log::error('ProductRepository::create a échoué', [
                'data'      => $data,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function update(Product $product, array $data): Product
    {
        try {
            $product->update($data);
            return $product->fresh();
        } catch (Throwable $e) {
            Log::error('ProductRepository::update a échoué', [
                'product_id' => $product->id,
                'data'       => $data,
                'exception'  => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function delete(Product $product): void
    {
        try {
            $product->delete();
        } catch (Throwable $e) {
            Log::error('ProductRepository::delete a échoué', [
                'product_id' => $product->id,
                'exception'  => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function searchPackable(string $search): \Illuminate\Database\Eloquent\Collection
    {
        return Product::where('can_be_packed', true)
            ->where('is_active', true)
            ->where(fn($q) => $q->where('name', 'like', "%$search%")->orWhere('barcode', 'like', "%$search%"))
            ->limit(10)
            ->get(['id', 'name', 'variation', 'buying_price', 'selling_price', 'stock_quantity', 'unit_id']);
    }

    /**
     * Ajuste le stock global ET le stock par magasin si warehouse_id est fourni.
     * Le mouvement de stock est toujours enregistré.
     *
     * Si $unitCost est fourni pour une entrée de stock (delta > 0), le prix d'achat
     * du produit est recalculé au coût moyen pondéré (CMP) :
     *   nouveau_prix = (stock_actuel × ancien_prix + delta × unitCost) / (stock_actuel + delta)
     */
    public function adjustStock(
        Product $product,
        int     $delta,
        string  $type,
        ?string $reference   = null,
        ?int    $packId      = null,
        ?string $note        = null,
        ?int    $warehouseId = null,
        ?float  $unitCost    = null
    ): void {
        try {
            // Stock global (Product.stock_quantity) + recalcul CMP si un coût d'achat est fourni
            if ($unitCost !== null && $delta > 0) {
                $oldQty   = max(0, (int) $product->stock_quantity);
                $oldPrice = (float) $product->buying_price;
                $newPrice = $oldQty > 0
                    ? (($oldQty * $oldPrice) + ($delta * $unitCost)) / ($oldQty + $delta)
                    : $unitCost;

                $product->increment('stock_quantity', $delta, ['buying_price' => round($newPrice, 2)]);
            } else {
                $product->increment('stock_quantity', $delta);
            }

            // Stock par magasin
            if ($warehouseId) {
                ProductStock::adjust($product->id, $warehouseId, $delta);
            }

            // Journal
            StockMovement::create([
                'product_id'   => $product->id,
                'warehouse_id' => $warehouseId,
                'quantity'     => $delta,
                'type'         => $type,
                'reference'    => $reference,
                'pack_id'      => $packId,
                'note'         => $note,
                'created_by'   => auth()->id(),
            ]);
        } catch (Throwable $e) {
            Log::error('ProductRepository::adjustStock a échoué', [
                'product_id'   => $product->id,
                'delta'        => $delta,
                'type'         => $type,
                'warehouse_id' => $warehouseId,
                'exception'    => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Synchronise les stocks initiaux d'un produit par magasin.
     * $stocks = [warehouse_id => quantity, ...]
     */
    public function syncWarehouseStocks(Product $product, array $stocks): void
    {
        try {
            $total = 0;
            foreach ($stocks as $warehouseId => $quantity) {
                $qty = max(0, (int) $quantity);
                ProductStock::updateOrCreate(
                    ['product_id' => $product->id, 'warehouse_id' => $warehouseId],
                    ['quantity' => $qty]
                );
                $total += $qty;
            }
            $product->update(['stock_quantity' => $total]);
        } catch (Throwable $e) {
            Log::error('ProductRepository::syncWarehouseStocks a échoué', [
                'product_id' => $product->id,
                'stocks'     => $stocks,
                'exception'  => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Retourne la quantité disponible dans un magasin précis.
     */
    public function stockInWarehouse(Product $product, int $warehouseId): int
    {
        return (int) ProductStock::where('product_id', $product->id)
            ->where('warehouse_id', $warehouseId)
            ->value('quantity') ?? 0;
    }
}
