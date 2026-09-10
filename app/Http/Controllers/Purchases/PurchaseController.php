<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\PaymentAccount;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Repositories\PackRepository;
use App\Services\PackStockService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly ProductRepository $productRepo,
        private readonly PackRepository    $packRepo,
        private readonly PackStockService  $stockService,
        private readonly PaymentService    $paymentService,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        try {
            $suppliers = Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']);
            return view('pages.purchases.index', compact('suppliers'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la page des achats');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function apiIndex(Request $request): JsonResponse
    {
        try {
            $paginator = Purchase::with(['supplier'])
                ->when($request->search,      fn($q, $s)  => $q->where('reference', 'like', "%$s%"))
                ->when($request->supplier_id, fn($q, $id) => $q->where('supplier_id', $id))
                ->when($request->status,      fn($q, $s)  => $q->where('status', $s))
                ->orderByDesc('purchase_date')
                ->orderByDesc('id')
                ->paginate(10, ['*'], 'page', $request->integer('page', 1));

            return response()->json([
                'data' => $paginator->getCollection()->map(fn($p) => [
                    'id'             => $p->id,
                    'reference'      => $p->reference,
                    'supplier'       => $p->supplier?->name ?? '—',
                    'purchase_date'  => \App\Helpers\FormatHelper::date($p->purchase_date),
                    'total'          => \App\Helpers\FormatHelper::money($p->total),
                    'status'         => $p->status,
                    'payment_status' => $p->payment_status,
                    'status_badge'   => \App\Helpers\FormatHelper::statusBadge($p->status),
                    'pay_badge'      => \App\Helpers\FormatHelper::statusBadge($p->payment_status),
                    'show_url'       => route('purchases.show', $p->id),
                    'edit_url'       => route('purchases.edit', $p->id),
                    'print_url'      => route('print.purchase', $p->id),
                    'confirm_url'    => route('purchases.confirm', $p->id),
                    'destroy_url'    => route('purchases.destroy', $p->id),
                ]),
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem() ?? 0,
                'to'           => $paginator->lastItem() ?? 0,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement des achats', ['filters' => $request->all()]);
            return response()->json(['message' => 'Impossible de charger les achats.'], 500);
        }
    }

    public function create(): View|RedirectResponse
    {
        try {
            $suppliers       = Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']);
            $firstSupplierId = $suppliers->first()?->id;
            $warehouses      = Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']);
            $categories      = Category::where('is_active', true)->orderBy('name')->get(['id', 'name']);
            $units           = Unit::orderBy('name')->get(['id', 'name', 'abbreviation']);
            $products        = Product::where('is_active', true)->orderBy('name')
                ->with('stocks')
                ->get(['id', 'name', 'variation', 'buying_price'])
                ->map(fn($p) => [
                    'id'           => $p->id,
                    'name'         => $p->display_name,
                    'buying_price' => (float) $p->buying_price,
                    'stocks'       => $p->stocks->pluck('quantity', 'warehouse_id'),
                ]);
            return view('pages.purchases.create', compact('suppliers', 'warehouses', 'categories', 'units', 'products', 'firstSupplierId'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du formulaire d\'achat');
            return redirect()->route('purchases.index')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'supplier_id'    => 'required|exists:suppliers,id',
            'warehouse_id'   => 'nullable|exists:warehouses,id',
            'purchase_date'  => 'required|date',
            'payment_type'   => 'nullable|in:pending,confirmed,paid',
            'items'          => 'required|array|min:1',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $type      = $request->payment_type ?? 'pending'; // pending | confirmed | paid
                $isPaid    = $type === 'paid';
                $isConfirm = $type === 'confirmed' || $isPaid;
                $reference = 'PUR-' . date('Ymd') . '-' . str_pad(Purchase::count() + 1, 4, '0', STR_PAD_LEFT);

                $purchase = Purchase::create([
                    'reference'      => $reference,
                    'supplier_id'    => $request->supplier_id,
                    'warehouse_id'   => $request->warehouse_id,
                    'purchase_date'  => $request->purchase_date,
                    'status'         => $isConfirm ? 'confirmed' : 'draft',
                    'payment_status' => $isPaid ? 'paid' : 'pending',
                    'note'           => $request->note,
                    'created_by'     => auth()->id(),
                ]);

                $subtotal    = 0;
                $warehouseId = $request->warehouse_id ? (int) $request->warehouse_id : null;

                foreach ($request->items as $item) {
                    $ispack       = ($item['item_type'] ?? 'product') === 'pack';
                    $unitsPerItem = $ispack ? ($item['units_per_item'] ?? 1) : 1;
                    $lineTotal    = $item['quantity'] * $item['unit_price'];
                    $subtotal    += $lineTotal;

                    PurchaseItem::create([
                        'purchase_id'    => $purchase->id,
                        'product_id'     => $ispack ? null : $item['product_id'],
                        'pack_id'        => $ispack ? $item['pack_id'] : null,
                        'item_type'      => $item['item_type'] ?? 'product',
                        'item_name'      => $item['item_name'],
                        'quantity'       => $item['quantity'],
                        'units_per_item' => $unitsPerItem,
                        'unit_price'     => $item['unit_price'],
                        'discount'       => 0,
                        'tax_rate'       => 0,
                        'subtotal'       => $lineTotal,
                    ]);

                    if ($isConfirm) {
                        if ($ispack) {
                            $this->stockService->addStockForPurchase($item['pack_id'], $item['quantity'], $reference, $warehouseId);
                        } else {
                            $product = \App\Models\Product::find($item['product_id']);
                            if ($product) {
                                $this->productRepo->adjustStock($product, $item['quantity'], 'purchase', $reference, null, null, $warehouseId, (float) $item['unit_price']);
                            }
                        }
                    }
                }

                $purchase->update([
                    'subtotal'    => $subtotal,
                    'total'       => $subtotal,
                    'amount_paid' => $isPaid ? $subtotal : 0,
                ]);

                if ($isPaid) {
                    $this->paymentService->recordOutflow($purchase, $subtotal, 'cash', null, $request->purchase_date);
                }
            });
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la création de l\'achat', ['request' => $request->except('_token')]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de l\'enregistrement de l\'achat.');
        }

        return redirect()->route('purchases.index')->with('success', 'Achat enregistré.');
    }

    public function show(Purchase $purchase): View|RedirectResponse
    {
        try {
            $purchase->load(['supplier', 'warehouse', 'items', 'payments.paymentAccount', 'returns']);
            $accounts = PaymentAccount::where('is_active', true)->orderBy('name')->get();
            return view('pages.purchases.show', compact('purchase', 'accounts'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de l\'achat', ['purchase_id' => $purchase->id]);
            return redirect()->route('purchases.index')->with('error', 'Une erreur est survenue lors du chargement de l\'achat.');
        }
    }

    public function pay(Request $request, Purchase $purchase): JsonResponse
    {
        abort_if($purchase->payment_status === 'paid', 409);

        $due = round((float) $purchase->total - (float) $purchase->amount_paid, 2);

        $request->validate([
            'amount'             => 'required|numeric|min:0.01|max:' . $due,
            'payment_mode'       => 'required|string',
            'payment_account_id' => 'required|exists:payment_accounts,id',
        ]);

        try {
            DB::transaction(function () use ($request, $purchase) {
                $wasDraft      = $purchase->status === 'draft';
                $newPaid       = round((float) $purchase->amount_paid + (float) $request->amount, 2);
                $paymentStatus = $newPaid >= (float) $purchase->total ? 'paid' : 'partial';

                $purchase->update([
                    'amount_paid'    => $newPaid,
                    'payment_status' => $paymentStatus,
                    'status'         => $wasDraft ? 'confirmed' : $purchase->status,
                ]);

                // Si le paiement confirme automatiquement un brouillon, ajuster le stock
                if ($wasDraft) {
                    $purchase->load('items');
                    $warehouseId = $purchase->warehouse_id;
                    foreach ($purchase->items as $item) {
                        if ($item->item_type === 'pack' && $item->pack_id) {
                            $this->stockService->addStockForPurchase($item->pack_id, $item->quantity, $purchase->reference, $warehouseId);
                        } elseif ($item->product_id) {
                            $product = Product::find($item->product_id);
                            if ($product) {
                                $this->productRepo->adjustStock($product, $item->quantity, 'purchase', $purchase->reference, null, null, $warehouseId, (float) $item->unit_price);
                            }
                        }
                    }
                }

                $this->paymentService->recordOutflow(
                    $purchase,
                    (float) $request->amount,
                    $request->payment_mode,
                    $request->payment_account_id ? (int) $request->payment_account_id : null
                );
            });
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de l\'enregistrement du paiement de l\'achat', ['purchase_id' => $purchase->id, 'request' => $request->except('_token')]);
            return response()->json(['message' => 'Une erreur est survenue lors de l\'enregistrement du paiement.'], 500);
        }

        $purchase->refresh();

        return response()->json([
            'success'        => true,
            'payment_status' => $purchase->payment_status,
            'amount_due'     => max(0, (float) $purchase->total - (float) $purchase->amount_paid),
        ]);
    }

    public function edit(Purchase $purchase): View|RedirectResponse
    {
        abort_unless($purchase->status === 'draft', 403);

        try {
            $purchase->load('items', 'supplier', 'warehouse');
            $suppliers  = Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']);
            $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']);
            $products   = Product::where('is_active', true)->orderBy('name')
                ->with('stocks')
                ->get(['id', 'name', 'variation', 'buying_price'])
                ->map(fn($p) => [
                    'id'           => $p->id,
                    'name'         => $p->display_name,
                    'buying_price' => (float) $p->buying_price,
                    'stocks'       => $p->stocks->pluck('quantity', 'warehouse_id'),
                ]);
            return view('pages.purchases.edit', compact('purchase', 'suppliers', 'warehouses', 'products'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du formulaire de modification d\'achat', ['purchase_id' => $purchase->id]);
            return redirect()->route('purchases.show', $purchase)->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function update(Request $request, Purchase $purchase): RedirectResponse
    {
        abort_unless($purchase->status === 'draft', 403);

        $request->validate([
            'supplier_id'   => 'required|exists:suppliers,id',
            'warehouse_id'  => 'nullable|exists:warehouses,id',
            'purchase_date' => 'required|date',
            'items'         => 'required|array|min:1',
        ]);

        $action = $request->input('action', 'save');

        try {
            DB::transaction(function () use ($request, $purchase, $action) {
                $purchase->items()->delete();

                $subtotal    = 0;
                $warehouseId = $request->warehouse_id ? (int) $request->warehouse_id : $purchase->warehouse_id;

                foreach ($request->items as $item) {
                    $lineTotal = $item['quantity'] * $item['unit_price'];
                    $subtotal += $lineTotal;
                    PurchaseItem::create([
                        'purchase_id'    => $purchase->id,
                        'product_id'     => $item['product_id'] ?: null,
                        'pack_id'        => null,
                        'item_type'      => 'product',
                        'item_name'      => $item['item_name'],
                        'quantity'       => $item['quantity'],
                        'units_per_item' => 1,
                        'unit_price'     => $item['unit_price'],
                        'discount'       => 0,
                        'tax_rate'       => 0,
                        'subtotal'       => $lineTotal,
                    ]);
                }

                $purchase->update([
                    'supplier_id'   => $request->supplier_id,
                    'warehouse_id'  => $request->warehouse_id,
                    'purchase_date' => $request->purchase_date,
                    'note'          => $request->note,
                    'subtotal'      => $subtotal,
                    'total'         => $subtotal,
                ]);

                if ($action === 'confirm') {
                    $purchase->refresh();
                    foreach ($purchase->items as $item) {
                        if ($item->product_id) {
                            $product = Product::find($item->product_id);
                            if ($product) {
                                $this->productRepo->adjustStock($product, $item->quantity, 'purchase', $purchase->reference, null, null, $warehouseId, (float) $item->unit_price);
                            }
                        }
                    }
                    $purchase->update(['status' => 'confirmed']);
                }
            });
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la mise à jour de l\'achat', ['purchase_id' => $purchase->id, 'request' => $request->except('_token')]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour de l\'achat.');
        }

        $msg = $action === 'confirm' ? 'Achat confirmé. Stock mis à jour.' : 'Achat mis à jour.';
        return redirect()->route('purchases.show', $purchase)->with('success', $msg);
    }

    public function confirm(Purchase $purchase): RedirectResponse
    {
        abort_unless($purchase->status === 'draft', 403);

        try {
            DB::transaction(function () use ($purchase) {
                $warehouseId = $purchase->warehouse_id;
                foreach ($purchase->items as $item) {
                    if ($item->item_type === 'pack' && $item->pack_id) {
                        $this->stockService->addStockForPurchase($item->pack_id, $item->quantity, $purchase->reference, $warehouseId);
                    } elseif ($item->product_id) {
                        $product = Product::find($item->product_id);
                        if ($product) {
                            $this->productRepo->adjustStock($product, $item->quantity, 'purchase', $purchase->reference, null, null, $warehouseId, (float) $item->unit_price);
                        }
                    }
                }
                $purchase->update(['status' => 'confirmed']);
            });
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la confirmation de l\'achat', ['purchase_id' => $purchase->id]);
            return back()->with('error', 'Une erreur est survenue lors de la confirmation de l\'achat.');
        }

        return back()->with('success', 'Achat confirmé. Stock mis à jour.');
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        try {
            $purchase->delete();
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la suppression de l\'achat', ['purchase_id' => $purchase->id]);
            return back()->with('error', 'Une erreur est survenue lors de la suppression de l\'achat.');
        }

        return redirect()->route('purchases.index')->with('success', 'Achat supprimé.');
    }
}
