<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\PosSession;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Repositories\ProductRepository;
use App\Services\PackStockService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class PosController extends Controller
{
    public function __construct(
        private readonly ProductRepository $productRepo,
        private readonly PackStockService  $stockService,
        private readonly PaymentService    $paymentService,
    ) {}

    public function index(): View|RedirectResponse
    {
        try {
        $activeSession = PosSession::where('user_id', auth()->id())
            ->whereNull('closed_at')
            ->with(['warehouse', 'caisse'])
            ->latest()
            ->first();

        $user = auth()->user();
        $isAdmin = $user->hasAnyRole(['Admin', 'Super Admin']);

        if ($activeSession && ! $isAdmin && ! $activeSession->caisse?->canBeOpenedBy($user)) {
            abort(404);
        }

        if ($activeSession && ! $isAdmin) {
            $verifiedSessionId = session()->pull('pos_access_verified_for');
            if ((int) $verifiedSessionId !== (int) $activeSession->id) {
                return view('pages.pos.session-access-code', compact('activeSession'));
            }
        }

        $customers          = Customer::with('group')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone', 'customer_group_id']);
        $warehouses         = Warehouse::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $managers           = \App\Models\User::where('is_active', true)->permission('manage pos')->orderBy('name')->get(['id', 'name']);
        $caisses            = \App\Models\Caisse::where('is_active', true)->orderBy('name')->get(['id', 'name', 'manager_id'])
            ->filter(fn($c) => $c->canBeOpenedBy(auth()->user()))
            ->values()
            ->map(fn($c) => ['id' => $c->id, 'name' => $c->name]);
        $categories         = \App\Models\Category::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $customerGroups     = CustomerGroup::orderBy('name')->get(['id', 'name', 'is_wholesale']);
        $defaultWarehouseId = (int) \App\Models\Setting::get('default_warehouse_id');
        return view('pages.pos.index', compact('customers', 'warehouses', 'caisses', 'categories', 'customerGroups', 'defaultWarehouseId', 'activeSession', 'managers'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la page POS');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function searchItems(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'q'            => 'nullable|string|max:191',
                'filter'       => 'nullable|string|in:packable',
                'category_id'  => 'nullable|integer|exists:categories,id',
                'warehouse_id' => 'nullable|integer|exists:warehouses,id',
                'customer_id'  => 'nullable|integer|exists:customers,id',
                'page'         => 'nullable|integer|min:1',
            ]);

            $q           = $validated['q'] ?? '';
            $warehouseId = $request->get('warehouse_id') ? (int) $request->get('warehouse_id') : null;
            $customer    = ! empty($validated['customer_id'])
                ? Customer::with('group')->findOrFail($validated['customer_id'])
                : null;
            $filter      = $validated['filter'] ?? null;
            $categoryId  = $validated['category_id'] ?? null;

            $products = \App\Models\Product::query()
                ->where('products.is_active', true)
                ->where(function ($query) use ($q) {
                    $query->where('products.name', 'like', "%$q%")
                        ->orWhere('products.variation', 'like', "%$q%");
                })
                ->when($filter === 'packable', fn ($query) => $query->where('products.can_be_packed', true))
                ->when($categoryId, fn ($query, $id) => $query->where('products.category_id', $id))
                ->when($warehouseId, fn($query) => $query->whereHas('stocks', fn($s) =>
                    $s->where('warehouse_id', $warehouseId)->where('quantity', '>', 0)
                ));

            $productsQuery = $products->select([
                'products.id',
                'products.name',
                'products.variation',
                'products.selling_price',
                'products.wholesale_price',
                'products.pack_price',
                'products.stock_quantity',
                'products.can_be_packed',
                'products.pack_quantity',
                'products.category_id',
            ])->selectRaw("'product' as type");

            $packsQuery = \App\Models\Pack::query()
                ->leftJoin('pack_prices', function ($join) {
                    $join->on('pack_prices.pack_id', '=', 'packs.id')
                        ->whereNull('pack_prices.price_group_id');
                })
                ->where('packs.is_active', true)
                ->where('packs.name', 'like', "%{$q}%")
                ->select([
                    'packs.id',
                    'packs.name',
                    DB::raw('NULL as variation'),
                    DB::raw('pack_prices.selling_price as selling_price'),
                    DB::raw('NULL as wholesale_price'),
                    DB::raw('NULL as pack_price'),
                    DB::raw('NULL as stock_quantity'),
                    DB::raw('0 as can_be_packed'),
                    DB::raw('1 as pack_quantity'),
                    DB::raw('NULL as category_id'),
                    DB::raw("'pack' as type"),
                ])
                ->when($filter === 'packable' || $categoryId, fn ($query) => $query->whereRaw('1 = 0'));

            $paginator = DB::query()
                ->fromSub($productsQuery->unionAll($packsQuery), 'catalog_items')
                ->orderBy('name')
                ->orderBy('type')
                ->paginate(15, ['*'], 'page', $request->integer('page', 1));

            $pageItems = $paginator->getCollection();
            $productIds = $pageItems->where('type', 'product')->pluck('id');
            $packIds = $pageItems->where('type', 'pack')->pluck('id');

            $productModels = \App\Models\Product::with([
                'media',
                'stocks' => fn ($query) => $query->when($warehouseId, fn ($stockQuery) => $stockQuery->where('warehouse_id', $warehouseId)),
            ])->whereIn('id', $productIds)->get()->keyBy('id');
            $packModels = \App\Models\Pack::with(['media', 'items.product'])
                ->with('defaultPrice')
                ->whereIn('id', $packIds)
                ->get()
                ->keyBy('id');

            $items = $pageItems->map(function ($item) use ($productModels, $packModels, $customer, $warehouseId) {
                if ($item->type === 'pack') {
                    $pack = $packModels->get($item->id);

                    return [
                        'id'          => $pack->id,
                        'name'        => $pack->name,
                        'price'       => $pack->default_selling_price,
                        'stock'       => app(PackStockService::class)->availablePackCount($pack->id, $warehouseId),
                        'type'        => 'pack',
                        'composition' => $pack->items->map(fn ($packItem) => "{$packItem->quantity}× {$packItem->product->display_name}")->implode(', '),
                        'image_url'   => $pack->getFirstMediaUrl('images') ?: null,
                    ];
                }

                $product = $productModels->get($item->id);

                return [
                    'id'              => $product->id,
                    'name'            => $product->display_name,
                    'price'           => $product->priceFor($customer),
                    'wholesale_price' => $product->wholesale_price !== null ? (float) $product->wholesale_price : null,
                    'stock'           => $warehouseId ? (int) ($product->stocks->first()?->quantity ?? 0) : $product->stock_quantity,
                    'can_be_packed'    => (bool) $product->can_be_packed,
                    'pack_quantity'    => (int) ($product->pack_quantity ?? 1),
                    'pack_price'       => (float) ($product->pack_price ?? ($product->selling_price * ($product->pack_quantity ?? 1))),
                    'image_url'        => $product->getFirstMediaUrl('images') ?: null,
                    'type'             => 'product',
                    'category_id'      => $product->category_id,
                ];
            })->values();

            return response()->json([
                'data'         => $items,
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la recherche d\'articles pour la vente', ['query' => $request->get('q')]);
            return response()->json(['message' => 'Une erreur est survenue lors de la recherche.'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'items'        => 'required|array|min:1',
            'payment_mode' => 'required|string',
            'amount_paid'  => 'required|numeric|min:0',
        ]);

        $activeSession = PosSession::where('user_id', auth()->id())
            ->whereNull('closed_at')->latest()->first();

        if (!$activeSession || !$activeSession->caisse_id) {
            return response()->json(['success' => false, 'message' => 'Aucune caisse ouverte.'], 403);
        }

        try {
            $sale = DB::transaction(function () use ($request, $activeSession) {
                $reference = 'POS-' . date('Ymd') . '-' . str_pad(Sale::where('is_pos', true)->count() + 1, 4, '0', STR_PAD_LEFT);

                $sale = Sale::create([
                    'reference'      => $reference,
                    'customer_id'    => $request->customer_id ?: null,
                    'warehouse_id'   => $activeSession->warehouse_id ?: ($request->warehouse_id ?: null),
                    'pos_session_id' => $activeSession->id,
                    'sale_date'      => now()->toDateString(),
                    'status'         => 'completed',
                    'payment_status' => (float) $request->amount_paid >= (float) $request->total ? 'paid' : 'partial',
                    'is_pos'         => true,
                    'note'           => $request->note,
                    'created_by'     => auth()->id(),
                ]);

                $subtotal = 0;
                foreach ($request->items as $item) {
                    $isPack = ($item['item_type'] ?? 'product') === 'pack';
                    $lineTotal = $item['quantity'] * $item['unit_price'];
                    $subtotal += $lineTotal;

                    $product  = $isPack ? null : \App\Models\Product::find($item['product_id']);
                    $unitCost = $isPack
                        ? (\App\Models\Pack::find($item['pack_id'])?->default_buying_price ?? 0)
                        : ($product?->buying_price ?? 0);

                    SaleItem::create([
                        'sale_id'        => $sale->id,
                        'product_id'     => $isPack ? null : $item['product_id'],
                        'pack_id'        => $isPack ? $item['pack_id'] : null,
                        'item_type'      => $item['item_type'] ?? 'product',
                        'item_name'      => $item['item_name'],
                        'quantity'       => $item['quantity'],
                        'units_per_item' => $isPack ? ($item['units_per_item'] ?? 1) : 1,
                        'unit_price'     => $item['unit_price'],
                        'unit_cost'      => $unitCost,
                        'discount'       => 0,
                        'tax_rate'       => 0,
                        'subtotal'       => $lineTotal,
                    ]);

                    $warehouseId = $activeSession->warehouse_id ?? null;

                    if ($isPack) {
                        $this->stockService->deductStockForSale($item['pack_id'], $item['quantity'], $reference, $warehouseId);
                    } elseif ($product) {
                        $unitsPerItem = (int) ($item['units_per_item'] ?? 1);
                        $this->productRepo->adjustStock($product, -($item['quantity'] * $unitsPerItem), 'sale', $reference, null, null, $warehouseId);
                    }
                }

                $amountPaid = (float) $request->amount_paid;

                $sale->update([
                    'subtotal'    => $subtotal,
                    'total'       => $subtotal,
                    'amount_paid' => $amountPaid,
                ]);

                $activeSession->increment('total_sales', $subtotal);

                if ($amountPaid > 0) {
                    $this->paymentService->recordInflow(
                        $sale,
                        min($amountPaid, $subtotal),
                        $request->payment_mode ?? 'cash',
                        $request->input('payment_account_id') ? (int) $request->input('payment_account_id') : null
                    );
                }

                return $sale;
            });
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de l\'enregistrement de la vente POS', [
                'session_id' => $activeSession->id,
                'request'    => $request->except('_token'),
            ]);
            return response()->json(['success' => false, 'message' => 'Une erreur est survenue lors de l\'enregistrement de la vente.'], 500);
        }

        return response()->json([
            'success'   => true,
            'reference' => $sale->reference,
            'sale_id'   => $sale->id,
            'change'    => max(0, (float) $request->amount_paid - $sale->total),
        ]);
    }

    public function pay(Request $request, Sale $sale): JsonResponse
    {
        abort_unless($sale->is_pos && $this->canAccessSale($sale, $request->user()), 404);

        $due = round((float) $sale->total - (float) $sale->amount_paid, 2);

        $request->validate([
            'amount'             => 'required|numeric|min:0.01|max:' . $due,
            'payment_mode'       => 'required|string',
            'payment_account_id' => 'nullable|exists:payment_accounts,id',
        ]);

        try {
            DB::transaction(function () use ($request, $sale) {
                $newPaid = round((float) $sale->amount_paid + (float) $request->amount, 2);
                $status  = $newPaid >= (float) $sale->total ? 'paid' : 'partial';

                $sale->update([
                    'amount_paid'    => $newPaid,
                    'payment_status' => $status,
                ]);

                $this->paymentService->recordInflow(
                    $sale,
                    (float) $request->amount,
                    $request->payment_mode,
                    $request->input('payment_account_id') ? (int) $request->input('payment_account_id') : null
                );
            });
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du paiement POS', ['sale_id' => $sale->id, 'request' => $request->except('_token')]);
            return response()->json(['message' => 'Une erreur est survenue lors de l\'enregistrement du paiement.'], 500);
        }

        $sale->refresh();

        return response()->json([
            'success'        => true,
            'payment_status' => $sale->payment_status,
            'amount_due'     => max(0, (float) $sale->total - (float) $sale->amount_paid),
        ]);
    }

    public function showByReference(Request $request, string $reference): View|RedirectResponse
    {
        $sale = Sale::where('reference', $reference)
            ->where('is_pos', true)
            ->when(
                ! $request->user()->hasAnyRole(['Admin', 'Super Admin']),
                fn ($query) => $query->where('created_by', $request->user()->id)
            )
            ->firstOrFail();

        try {
            $sale->load(['customer', 'items', 'posSession.caisse', 'warehouse', 'createdBy']);
            return view('pages.pos.ticket', compact('sale'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du ticket POS', ['sale_id' => $sale->id]);
            return redirect()->route('pos.index')->with('error', 'Une erreur est survenue lors du chargement du ticket.');
        }
    }

    public function receipt(Request $request, Sale $sale): View|RedirectResponse
    {
        abort_unless($sale->is_pos && $this->canAccessSale($sale, $request->user()), 404);

        try {
            $sale->load(['customer', 'items', 'warehouse', 'posSession.caisse', 'createdBy']);
            return view('print.pos-receipt', compact('sale'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du reçu POS', ['sale_id' => $sale->id]);
            return redirect()->route('pos.index')->with('error', 'Une erreur est survenue lors du chargement du reçu.');
        }
    }

    public function list(Request $request): View|RedirectResponse
    {
        try {
            return view('pages.pos.list');
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la liste POS');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function apiList(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $paginator = Sale::with(['customer'])
                ->where('is_pos', true)
                ->when(
                    ! $user->hasAnyRole(['Admin', 'Super Admin']),
                    fn ($query) => $query->where('created_by', $user->id)
                )
                ->when($request->date, fn($q, $d) => $q->whereDate('sale_date', $d))
                ->latest()
                ->paginate(10, ['*'], 'page', $request->integer('page', 1));

            return response()->json([
                'data' => $paginator->getCollection()->map(fn($s) => [
                    'id'             => $s->id,
                    'reference'      => $s->reference,
                    'customer'       => $s->customer?->name ?? 'Client comptoir',
                    'date_time'      => $s->created_at->format('d/m/Y H:i'),
                    'total'          => \App\Helpers\FormatHelper::money($s->total),
                    'amount_paid'    => \App\Helpers\FormatHelper::money($s->amount_paid),
                    'amount_due'     => (float) $s->amount_due,
                    'payment_status' => $s->payment_status,
                    'pay_badge'      => \App\Helpers\FormatHelper::statusBadge($s->payment_status),
                    'receipt_url'    => route('pos.receipt', $s->id),
                    'pay_url'        => '/pos/' . $s->id . '/pay',
                ]),
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem() ?? 0,
                'to'           => $paginator->lastItem() ?? 0,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement des ventes POS', ['filters' => $request->all()]);
            return response()->json(['message' => 'Impossible de charger les ventes.'], 500);
        }
    }

    private function canAccessSale(Sale $sale, User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Super Admin'])
            || (int) $sale->created_by === (int) $user->id;
    }
}
