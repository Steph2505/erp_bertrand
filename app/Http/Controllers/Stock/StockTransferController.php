<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Throwable;

class StockTransferController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        try {
            $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
            $products = Product::where('is_active', true)->orderBy('name')
                ->with('stocks')->get(['id', 'name', 'variation', 'unit_id'])
                ->map(fn($p) => ['id' => $p->id, 'name' => $p->display_name, 'stocks' => $p->stocks->pluck('quantity', 'warehouse_id')]);

            return view('pages.stock.transfers', compact('warehouses', 'products'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la page des transferts de stock');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function apiIndex(Request $request): JsonResponse
    {
        try {
            $paginator = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'createdBy'])
                ->when($request->search,       fn($q, $s)  => $q->where('reference', 'like', "%$s%"))
                ->when($request->status,       fn($q, $s)  => $q->where('status', $s))
                ->when($request->warehouse_id, fn($q, $id) =>
                    $q->where('from_warehouse_id', $id)->orWhere('to_warehouse_id', $id)
                )
                ->latest()
                ->paginate(10, ['*'], 'page', $request->integer('page', 1));

            return response()->json([
                'data' => $paginator->getCollection()->map(fn($t) => [
                    'id'            => $t->id,
                    'reference'     => $t->reference,
                    'from'          => $t->fromWarehouse?->name ?? '—',
                    'to'            => $t->toWarehouse?->name ?? '—',
                    'transfer_date' => \App\Helpers\FormatHelper::date($t->transfer_date),
                    'status'        => $t->status,
                    'status_badge'  => \App\Helpers\FormatHelper::statusBadge($t->status),
                    'note'          => $t->note ?? '—',
                ]),
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem() ?? 0,
                'to'           => $paginator->lastItem() ?? 0,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement des transferts de stock', ['filters' => $request->all()]);
            return response()->json(['message' => 'Impossible de charger les transferts.'], 500);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'from_warehouse_id'  => 'required|exists:warehouses,id',
            'to_warehouse_id'    => 'required|exists:warehouses,id|different:from_warehouse_id',
            'transfer_date'      => 'required|date',
            'note'               => 'nullable|string|max:500',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        $fromId = (int) $request->from_warehouse_id;
        $toId   = (int) $request->to_warehouse_id;

        // Vérifier que le stock source est suffisant par produit
        foreach ($request->items as $item) {
            $available = (int) ProductStock::where('product_id', $item['product_id'])
                ->where('warehouse_id', $fromId)
                ->value('quantity') ?? 0;

            if ($available < (int) $item['quantity']) {
                $productName = Product::find($item['product_id'])?->display_name ?? 'Produit';
                return back()->withErrors([
                    'items' => "Stock insuffisant pour « {$productName} » dans l'entrepôt source ({$available} disponible(s)).",
                ])->withInput();
            }
        }

        try {
            DB::transaction(function () use ($request, $fromId, $toId) {
            $reference = 'TRF-' . date('Ymd') . '-' . str_pad(StockTransfer::count() + 1, 4, '0', STR_PAD_LEFT);

            $transfer = StockTransfer::create([
                'reference'         => $reference,
                'from_warehouse_id' => $fromId,
                'to_warehouse_id'   => $toId,
                'transfer_date'     => $request->transfer_date,
                'status'            => 'received',
                'note'              => $request->note,
                'created_by'        => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $qty     = (int) $item['quantity'];

                // Enregistre la ligne de transfert
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id'        => $product->id,
                    'quantity'          => $qty,
                ]);

                // Débit du stock source
                ProductStock::adjust($product->id, $fromId, -$qty);
                StockMovement::create([
                    'product_id'   => $product->id,
                    'warehouse_id' => $fromId,
                    'quantity'     => -$qty,
                    'type'         => 'transfer',
                    'reference'    => $reference,
                    'note'         => "Sortie transfert → " . Warehouse::find($toId)?->name,
                    'created_by'   => auth()->id(),
                ]);

                // Crédit du stock destination
                ProductStock::adjust($product->id, $toId, $qty);
                StockMovement::create([
                    'product_id'   => $product->id,
                    'warehouse_id' => $toId,
                    'quantity'     => $qty,
                    'type'         => 'transfer',
                    'reference'    => $reference,
                    'note'         => "Entrée transfert ← " . Warehouse::find($fromId)?->name,
                    'created_by'   => auth()->id(),
                ]);

                // Le stock global (Product.stock_quantity) ne change pas (même total, redistribué)
            }
            });
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de l\'enregistrement du transfert de stock', ['request' => $request->except('_token')]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de l\'enregistrement du transfert.');
        }

        return back()->with('success', 'Transfert inter-site enregistré.');
    }
}
