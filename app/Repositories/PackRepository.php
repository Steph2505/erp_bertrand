<?php

namespace App\Repositories;

use App\Models\Pack;
use App\Models\PackItem;
use App\Models\PackPrice;
use App\Services\PackStockService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PackRepository
{
    public function __construct(private readonly PackStockService $stockService) {}

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return Pack::withCount('items')
            ->with(['items.product', 'defaultPrice'])
            ->when($filters['search'] ?? null, fn($q, $s) => $q->where('name', 'like', "%$s%"))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', $filters['is_active']))
            ->latest()
            ->paginate($perPage);
    }

    public function find(int $id): Pack
    {
        return Pack::with(['items.product.unit', 'prices.priceGroup'])->findOrFail($id);
    }

    public function create(array $data, array $items = [], array $prices = []): Pack
    {
        try {
            return DB::transaction(function () use ($data, $items, $prices) {
                $pack = Pack::create($data);
                $this->syncItems($pack, $items);
                $this->syncPrices($pack, $prices);
                return $pack;
            });
        } catch (Throwable $e) {
            Log::error('PackRepository::create a échoué', [
                'data'      => $data,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function update(Pack $pack, array $data, array $items = [], array $prices = []): Pack
    {
        try {
            return DB::transaction(function () use ($pack, $data, $items, $prices) {
                $pack->update($data);
                $this->syncItems($pack, $items);
                $this->syncPrices($pack, $prices);
                return $pack->fresh();
            });
        } catch (Throwable $e) {
            Log::error('PackRepository::update a échoué', [
                'pack_id'   => $pack->id,
                'data'      => $data,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function syncItems(Pack $pack, array $items): void
    {
        $pack->items()->delete();

        foreach ($items as $item) {
            if (!empty($item['product_id']) && !empty($item['quantity']) && $item['quantity'] > 0) {
                PackItem::create([
                    'pack_id'    => $pack->id,
                    'product_id' => $item['product_id'],
                    'quantity'   => (int) $item['quantity'],
                ]);
            }
        }
    }

    private function syncPrices(Pack $pack, array $prices): void
    {
        $pack->prices()->delete();

        foreach ($prices as $price) {
            PackPrice::create([
                'pack_id'        => $pack->id,
                'price_group_id' => $price['price_group_id'] ?: null,
                'buying_price'   => (float) ($price['buying_price'] ?? 0),
                'selling_price'  => (float) ($price['selling_price'] ?? 0),
            ]);
        }
    }

    public function delete(Pack $pack): void
    {
        try {
            $pack->delete();
        } catch (Throwable $e) {
            Log::error('PackRepository::delete a échoué', [
                'pack_id'   => $pack->id,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function toggleActive(Pack $pack): void
    {
        try {
            $pack->update(['is_active' => !$pack->is_active]);
        } catch (Throwable $e) {
            Log::error('PackRepository::toggleActive a échoué', [
                'pack_id'   => $pack->id,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function availableCount(Pack $pack): int
    {
        return $this->stockService->availablePackCount($pack->id);
    }

    public function searchForSale(string $query): array
    {
        $packs = Pack::with('items.product')
            ->where('is_active', true)
            ->where('name', 'like', "%$query%")
            ->limit(10)
            ->get();

        return $packs->map(fn($pack) => [
            'id'    => $pack->id,
            'name'  => $pack->name,
            'price' => $pack->default_selling_price,
            'stock' => $this->stockService->availablePackCount($pack->id),
            'type'  => 'pack',
            'items' => $pack->items->map(fn($i) => "{$i->quantity}× {$i->product->display_name}")->implode(', '),
        ])->toArray();
    }
}
