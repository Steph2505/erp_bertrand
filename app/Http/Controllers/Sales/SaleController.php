<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PaymentAccount;
use App\Models\Setting;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Warehouse;
use App\Repositories\ProductRepository;
use App\Services\PackStockService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function __construct(
        private readonly ProductRepository $productRepo,
        private readonly PackStockService  $stockService,
        private readonly PaymentService    $paymentService,
    ) {}

    public function index(Request $request): View
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return view('pages.sales.index', compact('customers'));
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $query = Sale::with(['customer'])
            ->withCount('items')
            ->when($request->search,      fn($q, $s)  => $q->where('reference', 'like', "%$s%"))
            ->when($request->customer_id, fn($q, $id) => $q->where('customer_id', $id))
            ->when($request->status,      fn($q, $s)  => $q->where('payment_status', $s))
            ->when($request->date_from,   fn($q, $d)  => $q->whereDate('sale_date', '>=', $d))
            ->when($request->date_to,     fn($q, $d)  => $q->whereDate('sale_date', '<=', $d))
            ->where('is_pos', false)
            ->orderByDesc('sale_date')
            ->orderByDesc('id');

        $periodTotal = (clone $query)->sum('total');
        $paginator   = $query->paginate(10, ['*'], 'page', $request->integer('page', 1));

        return response()->json([
            'data' => $paginator->getCollection()->map(fn($s) => [
                'id'             => $s->id,
                'reference'      => $s->reference,
                'customer'       => $s->customer?->name ?? null,
                'sale_date'      => \App\Helpers\FormatHelper::date($s->sale_date),
                'items_count'    => $s->items_count,
                'total'          => \App\Helpers\FormatHelper::money($s->total),
                'status'         => $s->status,
                'payment_status' => $s->payment_status,
                'status_badge'   => \App\Helpers\FormatHelper::statusBadge($s->status),
                'pay_badge'      => \App\Helpers\FormatHelper::statusBadge($s->payment_status),
                'amount_due'     => (float) $s->amount_due,
                'show_url'       => route('sales.show', $s->id),
                'edit_url'       => route('sales.edit', $s->id),
                'print_url'      => route('print.sale', $s->id),
                'confirm_url'    => route('sales.confirm', $s->id),
                'destroy_url'    => route('sales.destroy', $s->id),
            ]),
            'period_total' => \App\Helpers\FormatHelper::money($periodTotal),
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'from'         => $paginator->firstItem() ?? 0,
            'to'           => $paginator->lastItem() ?? 0,
        ]);
    }

    public function create(): View
    {
        $customers          = Customer::where('is_active', true)->orderBy('name')->get();
        $warehouses         = Warehouse::where('is_active', true)->orderBy('name')->get();
        $defaultWarehouseId = (int) Setting::get('default_warehouse_id');
        $firstCustomerId    = $customers->first()?->id;
        return view('pages.sales.create', compact('customers', 'warehouses', 'defaultWarehouseId', 'firstCustomerId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'customer_id'  => 'nullable|exists:customers,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'sale_date'    => 'required|date',
            'note'         => 'nullable|string|max:1000',
            'status'       => 'required|in:draft,confirmed,paid',
            'items'        => 'required|array|min:1',
        ]);

        $isConfirm   = in_array($request->status, ['confirmed', 'paid']);
        $warehouseId = $request->warehouse_id ? (int) $request->warehouse_id : null;

        // Vérifier le stock uniquement si la vente est confirmée/payée
        if ($isConfirm) {
            foreach ($request->items as $item) {
                if (($item['item_type'] ?? 'product') !== 'product') continue;
                $product = \App\Models\Product::find($item['product_id'] ?? null);
                if (!$product) continue;
                $available = $warehouseId
                    ? $this->productRepo->stockInWarehouse($product, $warehouseId)
                    : $product->stock_quantity;
                if ((int) $item['quantity'] > $available) {
                    return back()->withInput()->withErrors([
                        'items' => "Stock insuffisant pour « {$product->display_name} » — disponible : {$available}.",
                    ]);
                }
            }
        }

        DB::transaction(function () use ($request, $isConfirm, $warehouseId) {
            $isPaid    = $request->status === 'paid';
            $reference = 'SAL-' . date('Ymd') . '-' . str_pad(Sale::count() + 1, 4, '0', STR_PAD_LEFT);

            $sale = Sale::create([
                'reference'      => $reference,
                'customer_id'    => $request->customer_id,
                'warehouse_id'   => $request->warehouse_id,
                'sale_date'      => $request->sale_date,
                'status'         => $isPaid ? 'confirmed' : $request->status,
                'payment_status' => $isPaid ? 'paid' : 'pending',
                'note'           => $request->note,
                'created_by'     => auth()->id(),
            ]);

            $subtotal = 0;
            foreach ($request->items as $item) {
                $isPack       = ($item['item_type'] ?? 'product') === 'pack';
                $unitsPerItem = max(1, (int) ($item['units_per_item'] ?? 1));
                $lineTotal    = $item['quantity'] * $item['unit_price'];
                $subtotal    += $lineTotal;

                SaleItem::create([
                    'sale_id'        => $sale->id,
                    'product_id'     => $isPack ? null : $item['product_id'],
                    'pack_id'        => $isPack ? $item['pack_id'] : null,
                    'item_type'      => $item['item_type'] ?? 'product',
                    'item_name'      => $item['item_name'],
                    'quantity'       => $item['quantity'],
                    'units_per_item' => $unitsPerItem,
                    'unit_price'     => $item['unit_price'],
                    'discount'       => 0,
                    'tax_rate'       => 0,
                    'subtotal'       => $lineTotal,
                ]);

                // Mouvement de stock uniquement si confirmée/payée
                if ($isConfirm) {
                    if ($isPack) {
                        $this->stockService->deductStockForSale($item['pack_id'], $item['quantity'], $reference, $warehouseId);
                    } else {
                        $product = \App\Models\Product::find($item['product_id']);
                        if ($product) {
                            $qty = (int) $item['quantity'] * max(1, (int) ($item['units_per_item'] ?? 1));
                            $this->productRepo->adjustStock($product, -$qty, 'sale', $reference, null, null, $warehouseId);
                        }
                    }
                }
            }

            $sale->update([
                'subtotal'    => $subtotal,
                'total'       => $subtotal,
                'amount_paid' => $isPaid ? $subtotal : 0,
            ]);

            if ($isPaid) {
                $this->paymentService->recordInflow($sale, $subtotal, 'cash', null, $request->sale_date);
            }
        });

        return redirect()->route('sales.index')->with('success', 'Vente enregistrée.');
    }

    public function show(Sale $sale): View
    {
        $sale->load(['customer', 'warehouse', 'items', 'payments.paymentAccount']);
        $accounts = PaymentAccount::where('is_active', true)->orderBy('name')->get();
        return view('pages.sales.show', compact('sale', 'accounts'));
    }

    public function edit(Sale $sale): View
    {
        abort_unless($sale->status === 'draft', 403);
        $sale->load('items.product');
        $customers  = Customer::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        return view('pages.sales.edit', compact('sale', 'customers', 'warehouses'));
    }

    public function update(Request $request, Sale $sale): RedirectResponse
    {
        abort_unless($sale->status === 'draft', 403);

        $request->validate([
            'customer_id'  => 'nullable|exists:customers,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'sale_date'    => 'required|date',
            'note'         => 'nullable|string|max:1000',
            'items'        => 'required|array|min:1',
        ]);

        DB::transaction(function () use ($request, $sale) {
            $warehouseId = $sale->warehouse_id;

            foreach ($sale->items()->with('product')->get() as $old) {
                if ($old->item_type === 'pack' && $old->pack_id) {
                    $this->stockService->restoreStockForReturn($old->pack_id, $old->quantity, $sale->reference, $warehouseId);
                } elseif ($old->product_id && $old->product) {
                    $this->productRepo->adjustStock($old->product, $old->quantity * $old->units_per_item, 'sale_update', $sale->reference, null, null, $warehouseId);
                }
            }

            $sale->items()->delete();

            $newWarehouseId = $request->warehouse_id ? (int) $request->warehouse_id : $warehouseId;
            $subtotal = 0;
            foreach ($request->items as $item) {
                $isPack       = ($item['item_type'] ?? 'product') === 'pack';
                $unitsPerItem = max(1, (int) ($item['units_per_item'] ?? 1));
                $lineTotal    = $item['quantity'] * $item['unit_price'];
                $subtotal    += $lineTotal;

                SaleItem::create([
                    'sale_id'        => $sale->id,
                    'product_id'     => $isPack ? null : $item['product_id'],
                    'pack_id'        => $isPack ? $item['pack_id'] : null,
                    'item_type'      => $item['item_type'] ?? 'product',
                    'item_name'      => $item['item_name'],
                    'quantity'       => $item['quantity'],
                    'units_per_item' => $unitsPerItem,
                    'unit_price'     => $item['unit_price'],
                    'discount'       => 0,
                    'tax_rate'       => 0,
                    'subtotal'       => $lineTotal,
                ]);

                if ($isPack) {
                    $this->stockService->deductStockForSale($item['pack_id'], $item['quantity'], $sale->reference, $newWarehouseId);
                } else {
                    $product = \App\Models\Product::find($item['product_id']);
                    if ($product) {
                        $qty = (int) $item['quantity'] * max(1, (int) ($item['units_per_item'] ?? 1));
                        $this->productRepo->adjustStock($product, -$qty, 'sale', $sale->reference, null, null, $newWarehouseId);
                    }
                }
            }

            $sale->update([
                'customer_id'  => $request->customer_id,
                'warehouse_id' => $request->warehouse_id,
                'sale_date'    => $request->sale_date,
                'note'         => $request->note,
                'subtotal'     => $subtotal,
                'total'        => $subtotal,
                'amount_paid'  => 0,
            ]);
        });

        return redirect()->route('sales.show', $sale)->with('success', 'Vente mise à jour.');
    }

    public function pay(Request $request, Sale $sale): JsonResponse
    {
        abort_if($sale->is_pos, 404);
        abort_if($sale->payment_status === 'paid', 409);

        $due = round((float) $sale->total - (float) $sale->amount_paid, 2);

        $request->validate([
            'amount'             => 'required|numeric|min:0.01|max:' . $due,
            'payment_mode'       => 'required|string',
            'payment_account_id' => 'nullable|exists:payment_accounts,id',
        ]);

        DB::transaction(function () use ($request, $sale) {
            $wasDraft      = $sale->status === 'draft';
            $newPaid       = round((float) $sale->amount_paid + (float) $request->amount, 2);
            $paymentStatus = $newPaid >= (float) $sale->total ? 'paid' : 'partial';

            $sale->update([
                'amount_paid'    => $newPaid,
                'payment_status' => $paymentStatus,
                'status'         => $wasDraft ? 'confirmed' : $sale->status,
            ]);

            // Si le paiement confirme automatiquement un brouillon, déduire le stock
            if ($wasDraft) {
                $sale->load('items');
                $warehouseId = $sale->warehouse_id;
                foreach ($sale->items as $item) {
                    if ($item->item_type === 'pack' && $item->pack_id) {
                        $this->stockService->deductStockForSale($item->pack_id, $item->quantity, $sale->reference, $warehouseId);
                    } elseif ($item->product_id) {
                        $product = \App\Models\Product::find($item->product_id);
                        if ($product) {
                            $qty = $item->quantity * max(1, $item->units_per_item ?? 1);
                            $this->productRepo->adjustStock($product, -$qty, 'sale', $sale->reference, null, null, $warehouseId);
                        }
                    }
                }
            }

            $this->paymentService->recordInflow(
                $sale,
                (float) $request->amount,
                $request->payment_mode,
                $request->payment_account_id ? (int) $request->payment_account_id : null
            );
        });

        $sale->refresh();

        return response()->json([
            'success'        => true,
            'payment_status' => $sale->payment_status,
            'amount_due'     => max(0, (float) $sale->total - (float) $sale->amount_paid),
        ]);
    }

    public function confirm(Sale $sale): RedirectResponse
    {
        abort_if($sale->is_pos, 404);
        abort_unless($sale->status === 'draft', 403);

        DB::transaction(function () use ($sale) {
            $warehouseId = $sale->warehouse_id;
            foreach ($sale->items as $item) {
                if ($item->item_type === 'pack' && $item->pack_id) {
                    $this->stockService->deductStockForSale($item->pack_id, $item->quantity, $sale->reference, $warehouseId);
                } elseif ($item->product_id) {
                    $product = \App\Models\Product::find($item->product_id);
                    if ($product) {
                        $qty = $item->quantity * max(1, $item->units_per_item ?? 1);
                        $this->productRepo->adjustStock($product, -$qty, 'sale', $sale->reference, null, null, $warehouseId);
                    }
                }
            }
            $sale->update(['status' => 'confirmed']);
        });

        return back()->with('success', 'Vente confirmée. Stock mis à jour.');
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        if ($sale->status === 'confirmed') {
            return back()->withErrors(['sale' => 'Une vente confirmée ne peut pas être supprimée.']);
        }
        $sale->delete();
        return redirect()->route('sales.index')->with('success', 'Vente supprimée.');
    }

    public function searchItems(Request $request): \Illuminate\Http\JsonResponse
    {
        $q           = $request->get('q', '');
        $warehouseId = $request->get('warehouse_id') ? (int) $request->get('warehouse_id') : null;

        $products = \App\Models\Product::where('is_active', true)
            ->where('name', 'like', "%$q%")
            ->when($warehouseId, fn($query) => $query->whereHas('stocks', fn($s) =>
                $s->where('warehouse_id', $warehouseId)->where('quantity', '>', 0)
            ))
            ->limit(20)
            ->get(['id', 'name', 'variation', 'selling_price', 'pack_price', 'stock_quantity', 'can_be_packed', 'pack_quantity'])
            ->map(fn($p) => [
                'id'            => $p->id,
                'name'          => $p->display_name,
                'price'         => (float) $p->selling_price,
                'stock'         => $warehouseId ? $p->stockInWarehouse($warehouseId) : $p->stock_quantity,
                'can_be_packed' => (bool) $p->can_be_packed,
                'pack_quantity' => (int) ($p->pack_quantity ?? 1),
                'pack_price'    => (float) ($p->pack_price ?? ($p->selling_price * ($p->pack_quantity ?? 1))),
                'image_url'     => $p->getFirstMediaUrl('images') ?: null,
                'type'          => 'product',
            ]);

        $packs = \App\Models\Pack::where('is_active', true)
            ->where('name', 'like', "%$q%")
            ->with(['items.product', 'defaultPrice'])
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'price'       => $p->default_selling_price,
                'stock'       => app(PackStockService::class)->availablePackCount($p->id, $warehouseId),
                'type'        => 'pack',
                'composition' => $p->items->map(fn($i) => "{$i->quantity}× {$i->product->display_name}")->implode(', '),
                'image_url'   => $p->getFirstMediaUrl('images') ?: null,
            ]);

        return response()->json($products->concat($packs)->values());
    }
}
