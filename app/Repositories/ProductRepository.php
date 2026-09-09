<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use Illuminate\Pagination\LengthAwarePaginator;

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
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->fresh();
    }

    public function delete(Product $product): void
    {
        $product->delete();
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
     */
    public function adjustStock(
        Product $product,
        int     $delta,
        string  $type,
        ?string $reference   = null,
        ?int    $packId      = null,
        ?string $note        = null,
        ?int    $warehouseId = null
    ): void {
        // Stock global (Product.stock_quantity)
        $product->increment('stock_quantity', $delta);

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
    }

    /**
     * Synchronise les stocks initiaux d'un produit par magasin.
     * $stocks = [warehouse_id => quantity, ...]
     */
    public function syncWarehouseStocks(Product $product, array $stocks): void
    {
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
