<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Repositories\ProductRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function __construct(private readonly ProductRepository $productRepo) {}

    public function index(Request $request): View
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $products   = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'variation', 'stock_quantity', 'unit_id']);
        return view('pages.stock.adjustments', compact('warehouses', 'products'));
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $paginator = StockAdjustment::with(['warehouse', 'createdBy'])
            ->when($request->search, fn($q, $s) => $q->where('reference', 'like', "%$s%"))
            ->when($request->type,   fn($q, $t) => $q->where('type', $t))
            ->latest()
            ->paginate(10, ['*'], 'page', $request->integer('page', 1));

        return response()->json([
            'data' => $paginator->getCollection()->map(fn($a) => [
                'id'              => $a->id,
                'reference'       => $a->reference,
                'type'            => $a->type,
                'type_label'      => $a->type === 'addition' ? '+ Entrée' : '− Sortie',
                'type_badge'      => $a->type === 'addition' ? 'badge--green' : 'badge--red',
                'warehouse'       => $a->warehouse?->name ?? '—',
                'adjustment_date' => \App\Helpers\FormatHelper::date($a->adjustment_date),
                'reason'          => $a->reason ?? '—',
                'created_by'      => $a->createdBy?->name ?? '—',
            ]),
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'from'         => $paginator->firstItem() ?? 0,
            'to'           => $paginator->lastItem() ?? 0,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'warehouse_id'    => 'nullable|exists:warehouses,id',
            'adjustment_date' => 'required|date',
            'type'            => 'required|in:addition,subtraction',
            'reason'          => 'nullable|string|max:500',
            'items'           => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request) {
            $reference = 'ADJ-' . date('Ymd') . '-' . str_pad(StockAdjustment::count() + 1, 4, '0', STR_PAD_LEFT);

            StockAdjustment::create([
                'reference'       => $reference,
                'warehouse_id'    => $request->warehouse_id,
                'adjustment_date' => $request->adjustment_date,
                'type'            => $request->type,
                'reason'          => $request->reason,
                'created_by'      => auth()->id(),
            ]);

            $delta = $request->type === 'addition' ? 1 : -1;

            $warehouseId = $request->warehouse_id ? (int) $request->warehouse_id : null;

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $this->productRepo->adjustStock(
                    $product,
                    $delta * (int) $item['quantity'],
                    'adjustment',
                    $reference,
                    null,
                    $request->reason,
                    $warehouseId
                );
            }
        });

        return back()->with('success', 'Ajustement de stock enregistré.');
    }
}
