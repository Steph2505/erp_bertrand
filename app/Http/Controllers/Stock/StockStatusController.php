<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockStatusController extends Controller
{
    public function index(Request $request): View
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $categories = Category::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return view('pages.stock.status', compact('warehouses', 'categories'));
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');
        $categoryId  = $request->get('category_id');
        $status      = $request->get('status');
        $search      = $request->get('search');

        $query = Product::with(['category', 'unit', 'stocks' => function ($q) use ($warehouseId) {
                $warehouseId
                    ? $q->where('warehouse_id', $warehouseId)->with('warehouse')
                    : $q->with('warehouse');
            }])
            ->where('is_active', true)
            ->when($search,      fn($q, $s)  => $q->where('name', 'like', "%$s%"))
            ->when($categoryId,  fn($q, $id) => $q->where('category_id', $id))
            ->when($warehouseId, fn($q)      => $q->whereHas('stocks', fn($s) => $s->where('warehouse_id', $warehouseId)));

        if ($status === 'out') {
            $query->when($warehouseId,
                fn($q) => $q->whereHas('stocks', fn($s) => $s->where('warehouse_id', $warehouseId)->where('quantity', '<=', 0)),
                fn($q) => $q->where('stock_quantity', '<=', 0)
            );
        } elseif ($status === 'low') {
            $query->when($warehouseId,
                fn($q) => $q->whereHas('stocks', fn($s) => $s->where('warehouse_id', $warehouseId)->whereColumn('quantity', '<=', 'products.min_stock_quantity')),
                fn($q) => $q->whereRaw('stock_quantity <= min_stock_quantity')->where('stock_quantity', '>', 0)
            );
        }

        $paginator = $query->orderBy('name')->paginate(10, ['*'], 'page', $request->integer('page', 1));

        return response()->json([
            'data' => $paginator->getCollection()->map(function ($p) use ($warehouseId) {
                $unit = $p->unit?->abbreviation ?? 'u.';

                if ($warehouseId) {
                    $whStock = $p->stocks->first();
                    $qty     = $whStock?->quantity ?? 0;
                    $isLow   = $qty > 0 && $qty <= $p->min_stock_quantity;
                    $isOut   = $qty <= 0;
                    $stocks  = null;
                } else {
                    $qty    = $p->stock_quantity;
                    $isLow  = $qty > 0 && $qty <= $p->min_stock_quantity;
                    $isOut  = $qty <= 0;
                    $stocks = $p->stocks->map(fn($s) => [
                        'warehouse' => $s->warehouse?->name ?? '—',
                        'quantity'  => $s->quantity,
                    ])->values();
                }

                return [
                    'id'               => $p->id,
                    'name'             => $p->name,
                    'category'         => $p->category?->name ?? '—',
                    'unit'             => $unit,
                    'qty'              => $qty,
                    'min_qty'          => $p->min_stock_quantity,
                    'is_low'           => $isLow,
                    'is_out'           => $isOut,
                    'stocks'           => $stocks,
                    'show_url'         => route('products.show', $p->id),
                ];
            }),
            'warehouse_filter' => (bool) $warehouseId,
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'from'         => $paginator->firstItem() ?? 0,
            'to'           => $paginator->lastItem() ?? 0,
        ]);
    }
}
