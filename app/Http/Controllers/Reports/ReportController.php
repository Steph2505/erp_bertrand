<?php

namespace App\Http\Controllers\Reports;

use App\Helpers\ProfitHelper;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PackItem;
use App\Models\Payment;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class ReportController extends Controller
{
    private function dates(Request $request): array
    {
        return [
            $request->input('date_from', now()->startOfMonth()->toDateString()),
            $request->input('date_to',   now()->toDateString()),
        ];
    }

    // ── Profit / Perte ────────────────────────────────────────────────────────

    public function profitLoss(Request $request): View|RedirectResponse
    {
        try {
            // Par défaut : 1er janvier de l'année courante → aujourd'hui
            $from = $request->input('date_from', now()->startOfYear()->toDateString());
            $to   = $request->input('date_to',   now()->toDateString());

            return view('pages.reports.profit-loss', compact('from', 'to'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport profit/perte', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    public function apiProfitLoss(Request $request): JsonResponse
    {
        try {
            $from = $request->input('date_from', now()->startOfYear()->toDateString());
            $to   = $request->input('date_to',   now()->toDateString());

            $revenue  = (float) Sale::whereIn('status', ['confirmed', 'completed'])->whereBetween('sale_date', [$from, $to])->sum('total');
            $cogs     = ProfitHelper::productCogs($from, $to);
            $expenses = (float) Expense::whereBetween('expense_date', [$from, $to])->sum('amount');

            $grossProfit = $revenue - $cogs;
            $netProfit   = $grossProfit - $expenses;

            // Découpage mensuel strict de la période filtrée — aucun doublon possible
            $monthly = [];
            $cursor  = \Carbon\Carbon::parse($from)->startOfMonth();
            $endDate = \Carbon\Carbon::parse($to);

            while ($cursor->lte($endDate)) {
                $mStart = (string) max($cursor->copy()->startOfMonth()->toDateString(), $from);
                $mEnd   = (string) min($cursor->copy()->endOfMonth()->toDateString(),   $to);
                $mRevenue = (float) Sale::whereIn('status', ['confirmed', 'completed'])->whereBetween('sale_date', [$mStart, $mEnd])->sum('total');
                $mCogs    = ProfitHelper::productCogs($mStart, $mEnd);
                $monthly[] = [
                    'label'    => $cursor->isoFormat('MMM YYYY'),
                    'revenue'  => $mRevenue,
                    'cogs'     => $mCogs,
                    'profit'   => $mRevenue - $mCogs,
                    'expenses' => (float) Expense::whereBetween('expense_date', [$mStart, $mEnd])->sum('amount'),
                ];
                $cursor->addMonth();
            }

            $expensesByCategory = ExpenseCategory::withSum(
                ['expenses' => fn($q) => $q->whereBetween('expense_date', [$from, $to])], 'amount'
            )->get()->filter(fn($c) => $c->expenses_sum_amount > 0)->sortByDesc('expenses_sum_amount')->values();

            return response()->json([
                'revenue'      => $revenue,
                'cogs'         => $cogs,
                'expenses'     => $expenses,
                'gross_profit' => $grossProfit,
                'net_profit'   => $netProfit,
                'monthly'      => $monthly,
                'expenses_by_category' => $expensesByCategory->map(fn($c) => [
                    'name'   => $c->name,
                    'amount' => (float) $c->expenses_sum_amount,
                ]),
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport profit/perte', ['filters' => $request->all()]);
            return response()->json(['message' => 'Impossible de charger le rapport.'], 500);
        }
    }

    // ── Achat & Vente ─────────────────────────────────────────────────────────

    public function purchaseSale(Request $request): View|RedirectResponse
    {
        try {
            $year  = (int) $request->input('year', now()->year);
            $years = range(now()->year, max(now()->year - 4, 2024));

            $monthly = [];
            for ($m = 1; $m <= 12; $m++) {
                $start = sprintf('%d-%02d-01', $year, $m);
                $end   = sprintf('%d-%02d-%02d', $year, $m, cal_days_in_month(CAL_GREGORIAN, $m, $year));
                $s = (float) Sale::whereIn('status', ['confirmed', 'completed'])->whereBetween('sale_date', [$start, $end])->sum('total');
                $p = (float) Purchase::where('status', 'confirmed')->whereBetween('purchase_date', [$start, $end])->sum('total');
                $monthly[] = [
                    'label'     => \Carbon\Carbon::create($year, $m)->isoFormat('MMM'),
                    'sales'     => $s,
                    'purchases' => $p,
                    'margin'    => $s - $p,
                ];
            }

            $totals = [
                'sales'     => collect($monthly)->sum('sales'),
                'purchases' => collect($monthly)->sum('purchases'),
                'margin'    => collect($monthly)->sum('margin'),
            ];

            return view('pages.reports.purchase-sale', compact('monthly', 'totals', 'year', 'years'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport achat/vente', ['year' => $request->input('year')]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    // ── Rapport fiscal ────────────────────────────────────────────────────────

    public function tax(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            return view('pages.reports.tax', compact('from', 'to'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport fiscal', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    public function apiTax(Request $request): JsonResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            $taxCollected = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->whereIn('sales.status', ['confirmed', 'completed'])
                ->whereBetween('sales.sale_date', [$from, $to])
                ->where('sale_items.tax_rate', '>', 0)
                ->select(
                    'sale_items.tax_rate',
                    DB::raw('SUM(sale_items.subtotal) as base'),
                    DB::raw('SUM(sale_items.subtotal * sale_items.tax_rate / 100) as tax_amount')
                )
                ->groupBy('sale_items.tax_rate')
                ->get();

            $taxDeductible = PurchaseItem::join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                ->where('purchases.status', 'confirmed')
                ->whereBetween('purchases.purchase_date', [$from, $to])
                ->where('purchase_items.tax_rate', '>', 0)
                ->select(
                    'purchase_items.tax_rate',
                    DB::raw('SUM(purchase_items.subtotal) as base'),
                    DB::raw('SUM(purchase_items.subtotal * purchase_items.tax_rate / 100) as tax_amount')
                )
                ->groupBy('purchase_items.tax_rate')
                ->get();

            $totalCollected  = (float) $taxCollected->sum('tax_amount');
            $totalDeductible = (float) $taxDeductible->sum('tax_amount');
            $taxDue          = $totalCollected - $totalDeductible;

            return response()->json([
                'tax_collected' => $taxCollected->map(fn($r) => [
                    'tax_rate'   => (float) $r->tax_rate,
                    'base'       => (float) $r->base,
                    'tax_amount' => (float) $r->tax_amount,
                ]),
                'tax_deductible' => $taxDeductible->map(fn($r) => [
                    'tax_rate'   => (float) $r->tax_rate,
                    'base'       => (float) $r->base,
                    'tax_amount' => (float) $r->tax_amount,
                ]),
                'total_collected'  => $totalCollected,
                'total_deductible' => $totalDeductible,
                'tax_due'          => $taxDue,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport fiscal', ['filters' => $request->all()]);
            return response()->json(['message' => 'Impossible de charger le rapport.'], 500);
        }
    }

    // ── Clients (rapport) ────────────────────────────────────────────────────

    public function reportCustomers(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            // Catégories proposées dans le filtre (select personnalisé) — chaque
            // article est toujours rattaché à une catégorie (champ obligatoire).
            $categoryOptions = Category::orderBy('name')->pluck('name');

            // Clients proposés dans le filtre (select personnalisé).
            $customerOptions = Customer::orderBy('name')->pluck('name');

            return view('pages.reports.customers', compact('from', 'to', 'categoryOptions', 'customerOptions'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport clients', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    public function apiReportCustomers(Request $request): JsonResponse
    {
        try {
            [$from, $to] = $this->dates($request);
            $sort           = $request->input('sort', 'revenue'); // revenue|profit|name
            $categoryFilter = $request->input('category');
            $categoryId     = $categoryFilter ? Category::where('name', $categoryFilter)->value('id') : null;
            $customerFilter = $request->input('customer');

            // Un pack représente le même produit vendu par lot (ex: la douzaine de
            // bière plutôt que l'unité) — il hérite donc de la catégorie du produit
            // qu'il contient, comme dans le rapport « Bénéfice par catégorie ».
            $matchingPackIds = $categoryId
                ? PackItem::join('products', 'products.id', '=', 'pack_items.product_id')
                    ->groupBy('pack_items.pack_id')
                    ->select('pack_items.pack_id as pack_id', DB::raw('MIN(products.category_id) as category_id'))
                    ->pluck('category_id', 'pack_id')
                    ->filter(fn($catId) => $catId == $categoryId)
                    ->keys()
                : collect();

            // Coût figé au moment de la vente (unit_cost), comme ProfitHelper::productCogs —
            // reste exact même si le prix d'achat de l'article change ensuite.
            $costExpr = "SUM(CASE
                WHEN sale_items.item_type = 'pack' THEN sale_items.quantity * sale_items.unit_cost
                ELSE sale_items.quantity * sale_items.units_per_item * sale_items.unit_cost
            END)";

            $base = function () use ($from, $to, $categoryId, $matchingPackIds, $customerFilter) {
                $q = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->join('customers', 'customers.id', '=', 'sales.customer_id')
                    ->whereIn('sales.status', ['confirmed', 'completed'])
                    ->whereBetween('sales.sale_date', [$from, $to])
                    ->whereNull('sales.deleted_at')
                    ->when($customerFilter, fn($q2, $name) => $q2->where('customers.name', 'like', "%$name%"));

                if ($categoryId) {
                    $q->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
                        ->where(function ($q2) use ($categoryId, $matchingPackIds) {
                            $q2->where('products.category_id', $categoryId)
                                ->orWhereIn('sale_items.pack_id', $matchingPackIds);
                        });
                }

                return $q;
            };

            $query = $base()
                ->groupBy('customers.id', 'customers.name', 'customers.phone')
                ->select(
                    'customers.id as customer_id',
                    'customers.name as customer_name',
                    'customers.phone as customer_phone',
                    DB::raw('COUNT(DISTINCT sales.id) as nb_sales'),
                    DB::raw('SUM(sale_items.subtotal) as revenue'),
                    DB::raw("{$costExpr} as cost")
                )
                ->havingRaw('SUM(sale_items.subtotal) > 0');

            $query = match ($sort) {
                'profit' => $query->orderByRaw('(revenue - cost) desc'),
                'name'   => $query->orderBy('customers.name'),
                default  => $query->orderByDesc('revenue'),
            };

            // Jeu de données pour le diagramme : top 15 non paginé, même tri que la liste.
            $chartItems = (clone $query)->limit(15)->get();

            $paginator = $query->paginate($request->integer('per_page', 20), ['*'], 'page', $request->integer('page', 1));

            // Totaux sur tous les clients de la période filtrée, pas seulement la page affichée.
            $grandTotal = (float) $base()->sum('sale_items.subtotal');
            $grandCost  = (float) $base()->selectRaw($costExpr . ' as c')->value('c');

            return response()->json([
                'data' => $paginator->getCollection()->map(function ($r) {
                    $revenue = (float) $r->revenue;
                    $cost    = (float) $r->cost;
                    $profit  = $revenue - $cost;

                    return [
                        'id'          => $r->customer_id,
                        'name'        => $r->customer_name,
                        'phone'       => $r->customer_phone,
                        'show_url'    => route('customers.show', $r->customer_id),
                        'nb_sales'    => (int) $r->nb_sales,
                        'total_sales' => $revenue,
                        'cost'        => $cost,
                        'profit'      => $profit,
                        'margin'      => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0,
                    ];
                }),
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem() ?? 0,
                'to'           => $paginator->lastItem() ?? 0,
                'grand_total'  => $grandTotal,
                'grand_cost'   => $grandCost,
                'grand_profit' => $grandTotal - $grandCost,
                'chart_items'  => $chartItems->map(function ($r) {
                    $revenue = (float) $r->revenue;
                    $cost    = (float) $r->cost;
                    return [
                        'name'    => $r->customer_name,
                        'revenue' => $revenue,
                        'profit'  => $revenue - $cost,
                    ];
                }),
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport clients', ['filters' => $request->all()]);
            return response()->json(['message' => 'Impossible de charger le rapport.'], 500);
        }
    }

    // ── Fournisseurs (rapport) ───────────────────────────────────────────────

    public function reportSuppliers(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            // Fournisseurs proposés dans le filtre (select personnalisé).
            $supplierOptions = Supplier::orderBy('name')->pluck('name');

            return view('pages.reports.suppliers', compact('from', 'to', 'supplierOptions'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport fournisseurs', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    public function apiReportSuppliers(Request $request): JsonResponse
    {
        try {
            [$from, $to] = $this->dates($request);
            $supplierFilter = $request->input('supplier');

            $query = Supplier::query()
                ->select('suppliers.*',
                    DB::raw("(SELECT COUNT(*) FROM purchases WHERE purchases.supplier_id = suppliers.id AND purchases.status = 'confirmed' AND purchases.purchase_date BETWEEN '$from' AND '$to' AND purchases.deleted_at IS NULL) as nb_purchases"),
                    DB::raw("(SELECT COALESCE(SUM(total),0) FROM purchases WHERE purchases.supplier_id = suppliers.id AND purchases.status = 'confirmed' AND purchases.purchase_date BETWEEN '$from' AND '$to' AND purchases.deleted_at IS NULL) as total_purchases"),
                    // Dette : solde impayé sur tous les achats confirmés, indépendamment
                    // de la période filtrée — une dette ancienne reste due aujourd'hui.
                    DB::raw("(SELECT COALESCE(SUM(total - amount_paid),0) FROM purchases WHERE purchases.supplier_id = suppliers.id AND purchases.status = 'confirmed' AND purchases.payment_status != 'paid' AND purchases.deleted_at IS NULL) as debt")
                )
                ->when($supplierFilter, fn($q, $name) => $q->where('suppliers.name', 'like', "%$name%"))
                ->having('total_purchases', '>', 0)
                ->orderByDesc('total_purchases');

            // Jeu de données pour le diagramme : top 15 non paginé, même tri que la liste.
            $chartItems = (clone $query)->limit(15)->get();

            $paginator = $query->paginate($request->integer('per_page', 20), ['*'], 'page', $request->integer('page', 1));

            // Totaux sur tous les fournisseurs correspondant au filtre, pas
            // seulement la page affichée.
            $totalsBase = Purchase::where('status', 'confirmed')
                ->whereBetween('purchase_date', [$from, $to])
                ->when($supplierFilter, fn($q, $name) => $q->whereHas('supplier', fn($q2) => $q2->where('name', 'like', "%$name%")));
            $grandTotal = (float) $totalsBase->sum('total');

            $debtBase = Purchase::where('status', 'confirmed')
                ->where('payment_status', '!=', 'paid')
                ->when($supplierFilter, fn($q, $name) => $q->whereHas('supplier', fn($q2) => $q2->where('name', 'like', "%$name%")));
            $grandDebt = (float) $debtBase->sum(DB::raw('total - amount_paid'));

            return response()->json([
                'data' => $paginator->getCollection()->map(fn($s) => [
                    'id'              => $s->id,
                    'name'            => $s->name,
                    'show_url'        => route('suppliers.show', $s->id),
                    'nb_purchases'    => (int) $s->nb_purchases,
                    'total_purchases' => (float) $s->total_purchases,
                    'debt'            => (float) $s->debt,
                ]),
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem() ?? 0,
                'to'           => $paginator->lastItem() ?? 0,
                'grand_total'  => $grandTotal,
                'grand_debt'   => $grandDebt,
                'chart_items'  => $chartItems->map(fn($s) => [
                    'name'            => $s->name,
                    'total_purchases' => (float) $s->total_purchases,
                    'debt'            => (float) $s->debt,
                ]),
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport fournisseurs', ['filters' => $request->all()]);
            return response()->json(['message' => 'Impossible de charger le rapport.'], 500);
        }
    }

    // ── Rapport de stock ──────────────────────────────────────────────────────

    public function stock(Request $request): View|RedirectResponse
    {
        try {
            $categories = Category::orderBy('name')->get();
            $totalValue = (float) (Product::whereNull('deleted_at')->selectRaw('SUM(stock_quantity * buying_price) as v')->value('v') ?? 0);
            $lowCount   = Product::whereRaw('stock_quantity <= min_stock_quantity')->where('stock_quantity', '>', 0)->count();
            $outCount   = Product::where('stock_quantity', '<=', 0)->count();
            $totalItems = Product::count();

            return view('pages.reports.stock', compact('categories', 'totalValue', 'lowCount', 'outCount', 'totalItems'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport de stock', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    public function apiStock(Request $request): JsonResponse
    {
        try {
            $search     = $request->input('search');
            $categoryId = $request->input('category_id');
            $status     = $request->input('status');

            $paginator = Product::with(['category', 'unit'])
                ->when($search, fn($q) => $q->where('name', 'like', "%$search%"))
                ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
                ->when($status === 'low', fn($q) => $q->whereRaw('stock_quantity <= min_stock_quantity')->where('stock_quantity', '>', 0))
                ->when($status === 'out', fn($q) => $q->where('stock_quantity', '<=', 0))
                ->when($status === 'ok',  fn($q) => $q->whereRaw('stock_quantity > min_stock_quantity'))
                ->orderBy('name')
                ->paginate(30, ['*'], 'page', $request->integer('page', 1));

            return response()->json([
                'data' => $paginator->getCollection()->map(function (Product $p) {
                    $isOut = $p->stock_quantity <= 0;
                    $isLow = !$isOut && $p->stock_quantity <= $p->min_stock_quantity;
                    return [
                        'name'           => $p->display_name,
                        'category_name'  => $p->category?->name,
                        'unit_abbr'      => $p->unit?->abbreviation,
                        'stock_quantity' => (float) $p->stock_quantity,
                        'min_stock'      => (float) $p->min_stock_quantity,
                        'value'          => \App\Helpers\FormatHelper::money($p->stock_quantity * $p->buying_price),
                        'status'         => $isOut ? 'out' : ($isLow ? 'low' : 'ok'),
                    ];
                }),
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem() ?? 0,
                'to'           => $paginator->lastItem() ?? 0,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport de stock', ['filters' => $request->all()]);
            return response()->json(['message' => 'Impossible de charger le rapport.'], 500);
        }
    }

    // ── Ajustements de stock ──────────────────────────────────────────────────

    public function stockAdjustment(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            return view('pages.reports.stock-adjustment', compact('from', 'to'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport d\'ajustements de stock', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    public function apiStockAdjustment(Request $request): JsonResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            $paginator = StockMovement::with(['product', 'warehouse', 'createdBy'])
                ->whereIn('type', ['adjustment', 'addition', 'subtraction'])
                ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->latest()
                ->paginate(30, ['*'], 'page', $request->integer('page', 1));

            $totalAdded    = (float) StockMovement::whereIn('type', ['adjustment', 'addition'])->where('quantity', '>', 0)->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->sum('quantity');
            $totalDeducted = (float) abs(StockMovement::whereIn('type', ['adjustment', 'subtraction'])->where('quantity', '<', 0)->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->sum('quantity'));

            return response()->json([
                'data' => $paginator->getCollection()->map(fn($m) => [
                    'date'         => \App\Helpers\FormatHelper::date($m->created_at),
                    'product_name' => $m->product?->display_name,
                    'warehouse'    => $m->warehouse?->name,
                    'type'         => $m->type,
                    'quantity'     => (float) $m->quantity,
                    'note'         => $m->note,
                    'created_by'   => $m->createdBy?->name,
                ]),
                'total'          => $paginator->total(),
                'per_page'       => $paginator->perPage(),
                'current_page'   => $paginator->currentPage(),
                'last_page'      => $paginator->lastPage(),
                'from'           => $paginator->firstItem() ?? 0,
                'to'             => $paginator->lastItem() ?? 0,
                'total_added'    => $totalAdded,
                'total_deducted' => $totalDeducted,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport d\'ajustements de stock', ['filters' => $request->all()]);
            return response()->json(['message' => 'Impossible de charger le rapport.'], 500);
        }
    }

    // ── Achat par produit ─────────────────────────────────────────────────────

    public function productPurchase(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            return view('pages.reports.product-purchase', compact('from', 'to'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport achat par produit', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    public function apiProductPurchase(Request $request): JsonResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            $items = PurchaseItem::join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                ->where('purchases.status', 'confirmed')
                ->whereBetween('purchases.purchase_date', [$from, $to])
                ->whereNull('purchases.deleted_at')
                ->select(
                    'purchase_items.item_name',
                    'purchase_items.item_type',
                    DB::raw('SUM(purchase_items.quantity) as total_qty'),
                    DB::raw('SUM(purchase_items.subtotal) as total_amount'),
                    DB::raw('AVG(purchase_items.unit_price) as avg_price'),
                    DB::raw('COUNT(DISTINCT purchases.id) as nb_purchases')
                )
                ->groupBy('purchase_items.item_name', 'purchase_items.item_type')
                ->orderByDesc('total_amount')
                ->get();

            return response()->json([
                'items' => $items->map(fn($i) => [
                    'item_name'     => $i->item_name,
                    'item_type'     => $i->item_type,
                    'total_qty'     => (float) $i->total_qty,
                    'avg_price'     => (float) $i->avg_price,
                    'nb_purchases'  => (int) $i->nb_purchases,
                    'total_amount'  => (float) $i->total_amount,
                ]),
                'sum_qty'         => (float) $items->sum('total_qty'),
                'sum_nb_purchases' => (int) $items->sum('nb_purchases'),
                'sum_amount'      => (float) $items->sum('total_amount'),
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport achat par produit', ['filters' => $request->all()]);
            return response()->json(['message' => 'Impossible de charger le rapport.'], 500);
        }
    }

    // ── Vente par produit ─────────────────────────────────────────────────────

    public function productSale(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);
            $sort = $request->input('sort', 'revenue'); // revenue|name|qty|profit

            // Liste des articles déjà vendus, pour le filtre (select personnalisé).
            $articles = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->whereIn('sales.status', ['confirmed', 'completed'])
                ->whereNull('sales.deleted_at')
                ->distinct()
                ->orderBy('sale_items.item_name')
                ->pluck('sale_items.item_name');

            return view('pages.reports.product-sale', compact('from', 'to', 'sort', 'articles'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport vente par produit', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    public function apiProductSale(Request $request): JsonResponse
    {
        try {
            [$from, $to] = $this->dates($request);
            $sort = $request->input('sort', 'revenue'); // revenue|name|qty|profit
            $item = $request->input('item');

            $base = fn() => SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->whereIn('sales.status', ['confirmed', 'completed'])
                ->whereBetween('sales.sale_date', [$from, $to])
                ->whereNull('sales.deleted_at')
                ->when($item, fn($q) => $q->where('sale_items.item_name', $item));

            // Coût figé au moment de la vente (unit_cost), comme ProfitHelper::productCogs —
            // reste exact même si le prix d'achat de l'article change ensuite.
            $costExpr = "SUM(CASE
                WHEN sale_items.item_type = 'pack' THEN sale_items.quantity * sale_items.unit_cost
                ELSE sale_items.quantity * sale_items.units_per_item * sale_items.unit_cost
            END)";

            $query = $base()
                ->select(
                    'sale_items.item_name',
                    'sale_items.item_type',
                    DB::raw('SUM(sale_items.quantity) as total_qty'),
                    DB::raw('SUM(sale_items.subtotal) as total_revenue'),
                    DB::raw("{$costExpr} as total_cost"),
                    DB::raw("SUM(sale_items.subtotal) - ({$costExpr}) as total_profit"),
                    DB::raw('COUNT(DISTINCT sales.id) as nb_sales')
                )
                ->groupBy('sale_items.item_name', 'sale_items.item_type');

            $query = match ($sort) {
                'name'   => $query->orderBy('sale_items.item_name'),
                'qty'    => $query->orderByDesc('total_qty'),
                'profit' => $query->orderByDesc('total_profit'),
                default  => $query->orderByDesc('total_revenue'),
            };

            // Jeu de données pour le diagramme : top 15 non paginé, même tri que la liste.
            $chartItems = (clone $query)->limit(15)->get(['item_name', 'total_revenue', 'total_profit']);

            $paginator = $query->paginate($request->integer('per_page', 20), ['*'], 'page', $request->integer('page', 1));

            // Totaux sur toute la période filtrée, pas seulement la page affichée.
            $totalRevenue = (float) $base()->sum('sale_items.subtotal');
            $totalQty     = (int) $base()->sum('sale_items.quantity');
            $totalCost    = (float) $base()->selectRaw($costExpr . ' as c')->value('c');
            $totalProfit  = $totalRevenue - $totalCost;

            return response()->json([
                'data' => $paginator->getCollection()->map(fn($i) => [
                    'item_name'     => $i->item_name,
                    'item_type'     => $i->item_type,
                    'total_qty'     => (int) $i->total_qty,
                    'total_revenue' => (float) $i->total_revenue,
                    'total_cost'    => (float) $i->total_cost,
                    'total_profit'  => (float) $i->total_profit,
                    'nb_sales'      => (int) $i->nb_sales,
                ]),
                'total'         => $paginator->total(),
                'per_page'      => $paginator->perPage(),
                'current_page'  => $paginator->currentPage(),
                'last_page'     => $paginator->lastPage(),
                'from'          => $paginator->firstItem() ?? 0,
                'to'            => $paginator->lastItem() ?? 0,
                'total_revenue' => $totalRevenue,
                'total_qty'     => $totalQty,
                'total_cost'    => $totalCost,
                'total_profit'  => $totalProfit,
                'chart_items'   => $chartItems->map(fn($i) => [
                    'item_name'     => $i->item_name,
                    'total_revenue' => (float) $i->total_revenue,
                    'total_profit'  => (float) $i->total_profit,
                ]),
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport vente par produit', ['filters' => $request->all()]);
            return response()->json(['message' => 'Impossible de charger le rapport.'], 500);
        }
    }

    // ── Bénéfice par catégorie ───────────────────────────────────────────────────

    public function categoryProfit(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);
            $sort = $request->input('sort', 'profit'); // profit|revenue|name

            // Catégories proposées dans le filtre (select personnalisé) — chaque
            // article est toujours rattaché à une catégorie (champ obligatoire).
            $categoryOptions = Category::orderBy('name')->pluck('name');

            return view('pages.reports.category-profit', compact('from', 'to', 'sort', 'categoryOptions'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport bénéfice par catégorie', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    public function apiCategoryProfit(Request $request): JsonResponse
    {
        try {
            [$from, $to] = $this->dates($request);
            $sort           = $request->input('sort', 'profit'); // profit|revenue|name
            $categoryFilter = $request->input('category');

            // Un pack représente le même produit vendu par lot (ex: la douzaine de
            // bière plutôt que l'unité) — il n'a donc pas de catégorie propre, on
            // lui attribue celle du produit qu'il contient.
            $packCategoryIds = PackItem::join('products', 'products.id', '=', 'pack_items.product_id')
                ->groupBy('pack_items.pack_id')
                ->select('pack_items.pack_id as pack_id', DB::raw('MIN(products.category_id) as category_id'))
                ->pluck('category_id', 'pack_id');

            // Coût figé au moment de la vente (unit_cost), comme ProfitHelper::productCogs —
            // reste exact même si le prix d'achat du produit change ensuite.
            $rows = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
                ->whereIn('sales.status', ['confirmed', 'completed'])
                ->whereBetween('sales.sale_date', [$from, $to])
                ->whereNull('sales.deleted_at')
                ->groupBy('products.category_id', 'sale_items.item_type', 'sale_items.pack_id')
                ->select(
                    'products.category_id',
                    'sale_items.item_type',
                    'sale_items.pack_id',
                    DB::raw('SUM(sale_items.quantity) as qty'),
                    DB::raw('SUM(sale_items.subtotal) as revenue'),
                    DB::raw("SUM(CASE
                        WHEN sale_items.item_type = 'pack' THEN sale_items.quantity * sale_items.unit_cost
                        ELSE sale_items.quantity * sale_items.units_per_item * sale_items.unit_cost
                    END) as cost")
                )
                ->get();

            $categoryNames = Category::pluck('name', 'id');

            $categories = $rows
                ->map(function ($r) use ($packCategoryIds, $categoryNames) {
                    $categoryId = $r->item_type === 'pack'
                        ? ($packCategoryIds[$r->pack_id] ?? null)
                        : $r->category_id;

                    $revenue = (float) $r->revenue;
                    $cost    = (float) $r->cost;
                    $profit  = $revenue - $cost;

                    return (object) [
                        'category_id' => $categoryId,
                        'category'    => $categoryId ? ($categoryNames[$categoryId] ?? 'Sans catégorie') : 'Sans catégorie',
                        'qty'         => (int) $r->qty,
                        'revenue'     => $revenue,
                        'cost'        => $cost,
                        'profit'      => $profit,
                    ];
                })
                // Un pack peut retomber dans la même catégorie qu'une vente à l'unité
                // du même produit : on fusionne les deux lignes pour ne pas dupliquer
                // la catégorie dans le tableau.
                ->groupBy(fn($r) => $r->category_id ?? 'none')
                ->map(function ($group) {
                    $revenue = $group->sum('revenue');
                    $profit  = $group->sum('profit');
                    return (object) [
                        'category' => $group->first()->category,
                        'qty'      => $group->sum('qty'),
                        'revenue'  => $revenue,
                        'cost'     => $group->sum('cost'),
                        'profit'   => $profit,
                        'margin'   => $revenue > 0 ? round($profit / $revenue * 100, 1) : 0,
                    ];
                })
                ->values();

            if ($categoryFilter) {
                $categories = $categories->where('category', $categoryFilter)->values();
            }

            $categories = match ($sort) {
                'name'    => $categories->sortBy('category')->values(),
                'revenue' => $categories->sortByDesc('revenue')->values(),
                default   => $categories->sortByDesc('profit')->values(),
            };

            $totalRevenue = (float) $categories->sum('revenue');
            $totalCost    = (float) $categories->sum('cost');
            $totalProfit  = (float) $categories->sum('profit');
            $totalQty     = (int) $categories->sum('qty');
            $totalMargin  = $totalRevenue > 0 ? round($totalProfit / $totalRevenue * 100, 1) : 0;

            return response()->json([
                'categories'    => $categories->values(),
                'total_revenue' => $totalRevenue,
                'total_cost'    => $totalCost,
                'total_profit'  => $totalProfit,
                'total_qty'     => $totalQty,
                'total_margin'  => $totalMargin,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport bénéfice par catégorie', ['filters' => $request->all()]);
            return response()->json(['message' => 'Impossible de charger le rapport.'], 500);
        }
    }

    // ── Paiements d'achat ─────────────────────────────────────────────────────

    public function purchasePayments(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            return view('pages.reports.purchase-payments', compact('from', 'to'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport des paiements d\'achat', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    public function apiPurchasePayments(Request $request): JsonResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            $paginator = Payment::with(['paymentAccount'])
                ->where('payable_type', Purchase::class)
                ->whereBetween('payment_date', [$from, $to])
                ->latest('payment_date')
                ->paginate(30, ['*'], 'page', $request->integer('page', 1));

            $purchaseIds = $paginator->getCollection()->pluck('payable_id')->unique();
            $purchases   = Purchase::with('supplier')->whereIn('id', $purchaseIds)->get()->keyBy('id');

            $total = (float) Payment::where('payable_type', Purchase::class)
                ->whereBetween('payment_date', [$from, $to])
                ->sum('amount');

            return response()->json([
                'data' => $paginator->getCollection()->map(function ($pmt) use ($purchases) {
                    $purchase = $purchases[$pmt->payable_id] ?? null;
                    return [
                        'date'          => \App\Helpers\FormatHelper::date($pmt->payment_date),
                        'reference'     => $pmt->reference,
                        'purchase_ref'  => $purchase?->reference,
                        'purchase_url'  => $purchase ? route('purchases.show', $purchase->id) : null,
                        'supplier_name' => $purchase?->supplier?->name,
                        'payment_method' => $pmt->payment_method,
                        'account_name'  => $pmt->paymentAccount?->name,
                        'amount'        => \App\Helpers\FormatHelper::money($pmt->amount),
                    ];
                }),
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem() ?? 0,
                'to'           => $paginator->lastItem() ?? 0,
                'sum_amount'   => $total,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport des paiements d\'achat', ['filters' => $request->all()]);
            return response()->json(['message' => 'Impossible de charger le rapport.'], 500);
        }
    }

    // ── Paiements de vente ────────────────────────────────────────────────────

    public function salePayments(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            return view('pages.reports.sale-payments', compact('from', 'to'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport des paiements de vente', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    public function apiSalePayments(Request $request): JsonResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            $paginator = Payment::with(['paymentAccount'])
                ->where('payable_type', Sale::class)
                ->whereBetween('payment_date', [$from, $to])
                ->latest('payment_date')
                ->paginate(30, ['*'], 'page', $request->integer('page', 1));

            $saleIds = $paginator->getCollection()->pluck('payable_id')->unique();
            $sales   = Sale::with('customer')->whereIn('id', $saleIds)->get()->keyBy('id');

            $total = (float) Payment::where('payable_type', Sale::class)
                ->whereBetween('payment_date', [$from, $to])
                ->sum('amount');

            return response()->json([
                'data' => $paginator->getCollection()->map(function ($pmt) use ($sales) {
                    $sale = $sales[$pmt->payable_id] ?? null;
                    return [
                        'date'           => \App\Helpers\FormatHelper::date($pmt->payment_date),
                        'reference'      => $pmt->reference,
                        'sale_ref'       => $sale?->reference,
                        'sale_url'       => $sale ? route('pos.receipt', $sale->id) : null,
                        'customer_name'  => $sale?->customer?->name ?? 'Client comptoir',
                        'payment_method' => $pmt->payment_method,
                        'account_name'   => $pmt->paymentAccount?->name,
                        'amount'         => \App\Helpers\FormatHelper::money($pmt->amount),
                    ];
                }),
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem() ?? 0,
                'to'           => $paginator->lastItem() ?? 0,
                'sum_amount'   => $total,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport des paiements de vente', ['filters' => $request->all()]);
            return response()->json(['message' => 'Impossible de charger le rapport.'], 500);
        }
    }

    // ── Rapport de dépenses ───────────────────────────────────────────────────

    public function expenses(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            // Catégories proposées dans le filtre (select personnalisé) — les
            // deux postes agrégés (achats fournisseurs, non catégorisées)
            // s'ajoutent aux vraies catégories de dépenses.
            $categoryOptions = ExpenseCategory::orderBy('name')->pluck('name')
                ->push('Achats fournisseurs')
                ->push('Non catégorisée');

            return view('pages.reports.expenses', compact('from', 'to', 'categoryOptions'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport de dépenses', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    public function apiExpensesReport(Request $request): JsonResponse
    {
        try {
            [$from, $to] = $this->dates($request);
            $categoryFilter = $request->input('category');

            $byCategory = ExpenseCategory::withSum(
                ['expenses' => fn($q) => $q->whereBetween('expense_date', [$from, $to])], 'amount'
            )
            ->when($categoryFilter, fn($q, $name) => $q->where('name', $name))
            ->get()
            ->filter(fn($c) => ($c->expenses_sum_amount ?? 0) > 0)
            ->sortByDesc('expenses_sum_amount')
            ->values();

            $uncategorized = (!$categoryFilter || $categoryFilter === 'Non catégorisée')
                ? (float) Expense::whereNull('expense_category_id')
                    ->whereBetween('expense_date', [$from, $to])
                    ->sum('amount')
                : 0.0;

            // Achats fournisseurs payés : une sortie d'argent au même titre qu'une
            // dépense d'exploitation, regroupée comme une catégorie à part.
            $totalPurchases = (!$categoryFilter || $categoryFilter === 'Achats fournisseurs')
                ? (float) Purchase::where('payment_status', 'paid')
                    ->whereBetween('purchase_date', [$from, $to])
                    ->sum('amount_paid')
                : 0.0;

            $rows = $byCategory->map(fn($c) => [
                'category' => $c->name,
                'amount'   => (float) $c->expenses_sum_amount,
            ])->values();

            if ($uncategorized > 0) {
                $rows->push(['category' => 'Non catégorisée', 'amount' => $uncategorized]);
            }

            if ($totalPurchases > 0) {
                $rows->push(['category' => 'Achats fournisseurs', 'amount' => $totalPurchases]);
            }

            $rows  = $rows->sortByDesc('amount')->values();
            $total = (float) $rows->sum('amount');

            return response()->json([
                'rows'  => $rows,
                'total' => $total,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport de dépenses', ['filters' => $request->all()]);
            return response()->json(['message' => 'Impossible de charger le rapport.'], 500);
        }
    }

    // ── Rapport POS ───────────────────────────────────────────────────────────

    public function pos(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            return view('pages.reports.pos', compact('from', 'to'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport POS', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    public function apiPos(Request $request): JsonResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            $paginator = PosSession::with(['caisse', 'warehouse', 'user'])
                ->whereDate('opened_at', '>=', $from)
                ->whereDate('opened_at', '<=', $to)
                ->latest('opened_at')
                ->paginate(30, ['*'], 'page', $request->integer('page', 1));

            $totalSales = (float) PosSession::whereDate('opened_at', '>=', $from)
                ->whereDate('opened_at', '<=', $to)->sum('total_sales');

            $nbSessions = PosSession::whereDate('opened_at', '>=', $from)
                ->whereDate('opened_at', '<=', $to)->count();

            $avgPerSession = $nbSessions > 0 ? $totalSales / $nbSessions : 0;

            return response()->json([
                'data' => $paginator->getCollection()->map(function (PosSession $s) {
                    $expected = (float) $s->opening_balance + (float) $s->total_sales;
                    $variance = $s->closed_at ? ((float) $s->closing_balance - $expected) : null;
                    return [
                        'id'               => $s->id,
                        'opened_at'        => \App\Helpers\FormatHelper::datetime($s->opened_at),
                        'show_url'         => route('pos.sessions.show', $s->id),
                        'caisse_name'      => $s->caisse?->name,
                        'user_name'        => $s->user?->name,
                        'warehouse_name'   => $s->warehouse?->name,
                        'opening_balance'  => \App\Helpers\FormatHelper::money((float) $s->opening_balance),
                        'total_sales'      => \App\Helpers\FormatHelper::money((float) $s->total_sales),
                        'closing_balance'  => $s->closed_at ? \App\Helpers\FormatHelper::money((float) $s->closing_balance) : null,
                        'variance'         => $variance !== null ? \App\Helpers\FormatHelper::money($variance) : null,
                        'variance_sign'    => $variance !== null ? ($variance >= 0 ? 1 : -1) : 0,
                        'is_closed'        => (bool) $s->closed_at,
                    ];
                }),
                'total'           => $paginator->total(),
                'per_page'        => $paginator->perPage(),
                'current_page'    => $paginator->currentPage(),
                'last_page'       => $paginator->lastPage(),
                'from'            => $paginator->firstItem() ?? 0,
                'to'              => $paginator->lastItem() ?? 0,
                'total_sales_sum' => \App\Helpers\FormatHelper::money($totalSales),
                'nb_sessions'     => $nbSessions,
                'avg_per_session' => \App\Helpers\FormatHelper::money($avgPerSession),
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport POS', ['filters' => $request->all()]);
            return response()->json(['message' => 'Impossible de charger le rapport.'], 500);
        }
    }

    // ── Représentants ─────────────────────────────────────────────────────────

    public function agents(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            return view('pages.reports.agents', compact('from', 'to'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport des représentants', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    public function apiAgents(Request $request): JsonResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            $agents = DB::table('sales')
                ->join('users', 'users.id', '=', 'sales.created_by')
                ->whereIn('sales.status', ['confirmed', 'completed'])
                ->whereBetween('sales.sale_date', [$from, $to])
                ->whereNull('sales.deleted_at')
                ->select(
                    'users.id',
                    'users.name',
                    DB::raw('COUNT(sales.id) as nb_sales'),
                    DB::raw('SUM(sales.total) as total_revenue'),
                    DB::raw("SUM(CASE WHEN sales.is_pos = 1 THEN sales.total ELSE 0 END) as pos_revenue"),
                    DB::raw("SUM(CASE WHEN sales.is_pos = 0 THEN sales.total ELSE 0 END) as direct_revenue")
                )
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('total_revenue')
                ->get();

            $grandTotal = (float) $agents->sum('total_revenue');

            return response()->json([
                'agents' => $agents->map(fn($a) => [
                    'name'            => $a->name,
                    'nb_sales'        => (int) $a->nb_sales,
                    'direct_revenue'  => (float) $a->direct_revenue,
                    'pos_revenue'     => (float) $a->pos_revenue,
                    'total_revenue'   => (float) $a->total_revenue,
                    'share'           => $grandTotal > 0 ? round(((float) $a->total_revenue / $grandTotal) * 100, 1) : 0,
                ]),
                'grand_total'  => $grandTotal,
                'sum_nb_sales' => (int) $agents->sum('nb_sales'),
                'sum_direct'   => (float) $agents->sum('direct_revenue'),
                'sum_pos'      => (float) $agents->sum('pos_revenue'),
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport des représentants', ['filters' => $request->all()]);
            return response()->json(['message' => 'Impossible de charger le rapport.'], 500);
        }
    }

    // ── Journal d'activité ────────────────────────────────────────────────────

    public function activity(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            return view('pages.reports.activity', compact('from', 'to'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du journal d\'activité', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    public function apiActivity(Request $request): JsonResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            $paginator = ActivityLog::with('user')
                ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->latest()
                ->paginate(10, ['*'], 'page', $request->integer('page', 1));

            return response()->json([
                'data' => $paginator->getCollection()->map(fn($log) => [
                    'created_at'  => \App\Helpers\FormatHelper::datetime($log->created_at),
                    'user_name'   => $log->user?->name,
                    'action'      => $log->action,
                    'description' => $log->description,
                    'ip_address'  => $log->ip_address,
                ]),
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem() ?? 0,
                'to'           => $paginator->lastItem() ?? 0,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du journal d\'activité', ['filters' => $request->all()]);
            return response()->json(['message' => 'Impossible de charger le rapport.'], 500);
        }
    }
}
