<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Payment;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Supplier;
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

    /**
     * Coût réel des produits vendus (POS + Vente confondus) sur la période,
     * calculé ligne par ligne à partir du prix d'achat au moment de la lecture
     * (produits : quantité × unités/pack × prix d'achat ; packs : quantité × prix d'achat du pack).
     */
    private function productCogs(string $from, string $to): float
    {
        // Coût figé sur chaque ligne de vente (unit_cost) au moment de la vente —
        // reste exact même si le prix d'achat du produit change ensuite (CMP).
        return (float) SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereIn('sales.status', ['confirmed', 'completed'])
            ->whereBetween('sales.sale_date', [$from, $to])
            ->whereNull('sales.deleted_at')
            ->selectRaw("COALESCE(SUM(
                CASE
                    WHEN sale_items.item_type = 'pack' THEN sale_items.quantity * sale_items.unit_cost
                    ELSE sale_items.quantity * sale_items.units_per_item * sale_items.unit_cost
                END
            ), 0) as total")
            ->value('total');
    }

    // ── Profit / Perte ────────────────────────────────────────────────────────

    public function profitLoss(Request $request): View|RedirectResponse
    {
        try {
            // Par défaut : 1er janvier de l'année courante → aujourd'hui
            $from = $request->input('date_from', now()->startOfYear()->toDateString());
            $to   = $request->input('date_to',   now()->toDateString());

            $revenue  = (float) Sale::whereIn('status', ['confirmed', 'completed'])->whereBetween('sale_date', [$from, $to])->sum('total');
            $cogs     = $this->productCogs($from, $to);
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
                $mCogs    = $this->productCogs($mStart, $mEnd);
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
            )->get()->filter(fn($c) => $c->expenses_sum_amount > 0)->sortByDesc('expenses_sum_amount');

            return view('pages.reports.profit-loss', compact(
                'revenue', 'cogs', 'expenses', 'grossProfit', 'netProfit',
                'monthly', 'expensesByCategory', 'from', 'to'
            ));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport profit/perte', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
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

            return view('pages.reports.tax', compact(
                'taxCollected', 'taxDeductible', 'totalCollected', 'totalDeductible', 'taxDue', 'from', 'to'
            ));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport fiscal', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    // ── Fournisseurs & Clients ────────────────────────────────────────────────

    public function contacts(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            $topCustomers = Customer::query()
                ->select('customers.*',
                    DB::raw("(SELECT COUNT(*) FROM sales WHERE sales.customer_id = customers.id AND sales.status IN ('confirmed','completed') AND sales.sale_date BETWEEN '$from' AND '$to' AND sales.deleted_at IS NULL) as nb_sales"),
                    DB::raw("(SELECT COALESCE(SUM(total),0) FROM sales WHERE sales.customer_id = customers.id AND sales.status IN ('confirmed','completed') AND sales.sale_date BETWEEN '$from' AND '$to' AND sales.deleted_at IS NULL) as total_sales")
                )
                ->having('total_sales', '>', 0)
                ->orderByDesc('total_sales')
                ->limit(20)
                ->get();

            $topSuppliers = Supplier::query()
                ->select('suppliers.*',
                    DB::raw("(SELECT COUNT(*) FROM purchases WHERE purchases.supplier_id = suppliers.id AND purchases.status = 'confirmed' AND purchases.purchase_date BETWEEN '$from' AND '$to' AND purchases.deleted_at IS NULL) as nb_purchases"),
                    DB::raw("(SELECT COALESCE(SUM(total),0) FROM purchases WHERE purchases.supplier_id = suppliers.id AND purchases.status = 'confirmed' AND purchases.purchase_date BETWEEN '$from' AND '$to' AND purchases.deleted_at IS NULL) as total_purchases")
                )
                ->having('total_purchases', '>', 0)
                ->orderByDesc('total_purchases')
                ->limit(20)
                ->get();

            return view('pages.reports.contacts', compact('topCustomers', 'topSuppliers', 'from', 'to'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport contacts', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    // ── Rapport de stock ──────────────────────────────────────────────────────

    public function stock(Request $request): View|RedirectResponse
    {
        try {
            $search     = $request->input('search');
            $categoryId = $request->input('category_id');
            $status     = $request->input('status');

            $products = Product::with(['category', 'unit'])
                ->when($search, fn($q) => $q->where('name', 'like', "%$search%"))
                ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
                ->when($status === 'low', fn($q) => $q->whereRaw('stock_quantity <= min_stock_quantity')->where('stock_quantity', '>', 0))
                ->when($status === 'out', fn($q) => $q->where('stock_quantity', '<=', 0))
                ->when($status === 'ok',  fn($q) => $q->whereRaw('stock_quantity > min_stock_quantity'))
                ->orderBy('name')
                ->paginate(30)
                ->withQueryString();

            $categories = Category::orderBy('name')->get();
            $totalValue = (float) (Product::whereNull('deleted_at')->selectRaw('SUM(stock_quantity * buying_price) as v')->value('v') ?? 0);
            $lowCount   = Product::whereRaw('stock_quantity <= min_stock_quantity')->where('stock_quantity', '>', 0)->count();
            $outCount   = Product::where('stock_quantity', '<=', 0)->count();
            $totalItems = Product::count();

            return view('pages.reports.stock', compact(
                'products', 'categories', 'totalValue', 'lowCount', 'outCount', 'totalItems',
                'search', 'categoryId', 'status'
            ));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport de stock', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    // ── Péremption ────────────────────────────────────────────────────────────

    public function expiry(Request $request): View|RedirectResponse
    {
        try {
            $products = Product::whereNotNull('expiry_date')
                ->with(['category', 'unit'])
                ->orderBy('expiry_date')
                ->get()
                ->map(function ($p) {
                    $days = (int) now()->startOfDay()->diffInDays($p->expiry_date, false);
                    return (object) [
                        'name'        => $p->display_name,
                        'category'    => $p->category?->name,
                        'unit'        => $p->unit?->abbreviation,
                        'stock'       => $p->stock_quantity,
                        'expiry_date' => $p->expiry_date,
                        'days_left'   => $days,
                        'status'      => $days < 0 ? 'expired' : ($days <= 30 ? 'warning' : 'ok'),
                    ];
                });

            $expired = $products->where('status', 'expired')->count();
            $warning = $products->where('status', 'warning')->count();
            $ok      = $products->where('status', 'ok')->count();

            return view('pages.reports.expiry', compact('products', 'expired', 'warning', 'ok'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport de péremption');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    // ── Ajustements de stock ──────────────────────────────────────────────────

    public function stockAdjustment(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            $movements = StockMovement::with(['product', 'warehouse', 'createdBy'])
                ->whereIn('type', ['adjustment', 'addition', 'subtraction'])
                ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->latest()
                ->paginate(30)
                ->withQueryString();

            $totalAdded    = (float) StockMovement::whereIn('type', ['adjustment', 'addition'])->where('quantity', '>', 0)->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->sum('quantity');
            $totalDeducted = (float) abs(StockMovement::whereIn('type', ['adjustment', 'subtraction'])->where('quantity', '<', 0)->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])->sum('quantity'));

            return view('pages.reports.stock-adjustment', compact('movements', 'totalAdded', 'totalDeducted', 'from', 'to'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport d\'ajustements de stock', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    // ── Produits en tendance ──────────────────────────────────────────────────

    public function trending(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            $items = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->whereIn('sales.status', ['confirmed', 'completed'])
                ->whereBetween('sales.sale_date', [$from, $to])
                ->whereNull('sales.deleted_at')
                ->select(
                    'sale_items.item_name',
                    'sale_items.item_type',
                    DB::raw('SUM(sale_items.quantity) as total_qty'),
                    DB::raw('SUM(sale_items.subtotal) as total_revenue'),
                    DB::raw('COUNT(DISTINCT sales.id) as nb_sales')
                )
                ->groupBy('sale_items.item_name', 'sale_items.item_type')
                ->orderByDesc('total_revenue')
                ->limit(30)
                ->get();

            return view('pages.reports.trending', compact('items', 'from', 'to'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport des tendances', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    // ── Rapport des articles ──────────────────────────────────────────────────

    public function items(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            $items = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->whereIn('sales.status', ['confirmed', 'completed'])
                ->whereBetween('sales.sale_date', [$from, $to])
                ->whereNull('sales.deleted_at')
                ->select(
                    'sale_items.item_name',
                    'sale_items.item_type',
                    DB::raw('SUM(sale_items.quantity) as total_qty'),
                    DB::raw('SUM(sale_items.subtotal) as total_revenue'),
                    DB::raw('AVG(sale_items.unit_price) as avg_price'),
                    DB::raw('COUNT(DISTINCT sales.id) as nb_sales')
                )
                ->groupBy('sale_items.item_name', 'sale_items.item_type')
                ->orderBy('sale_items.item_name')
                ->get();

            return view('pages.reports.items', compact('items', 'from', 'to'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport des articles', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    // ── Achat par produit ─────────────────────────────────────────────────────

    public function productPurchase(Request $request): View|RedirectResponse
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

            return view('pages.reports.product-purchase', compact('items', 'from', 'to'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport achat par produit', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    // ── Vente par produit ─────────────────────────────────────────────────────

    public function productSale(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            $items = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->whereIn('sales.status', ['confirmed', 'completed'])
                ->whereBetween('sales.sale_date', [$from, $to])
                ->whereNull('sales.deleted_at')
                ->select(
                    'sale_items.item_name',
                    'sale_items.item_type',
                    DB::raw('SUM(sale_items.quantity) as total_qty'),
                    DB::raw('SUM(sale_items.subtotal) as total_revenue'),
                    DB::raw('AVG(sale_items.unit_price) as avg_price'),
                    DB::raw('COUNT(DISTINCT sales.id) as nb_sales')
                )
                ->groupBy('sale_items.item_name', 'sale_items.item_type')
                ->orderByDesc('total_revenue')
                ->get();

            return view('pages.reports.product-sale', compact('items', 'from', 'to'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport vente par produit', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    // ── Paiements d'achat ─────────────────────────────────────────────────────

    public function purchasePayments(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            $payments = Payment::with(['paymentAccount'])
                ->where('payable_type', Purchase::class)
                ->whereBetween('payment_date', [$from, $to])
                ->latest('payment_date')
                ->paginate(30)
                ->withQueryString();

            // Récupérer les références des achats liés
            $purchaseIds = $payments->pluck('payable_id')->unique();
            $purchases   = Purchase::with('supplier')->whereIn('id', $purchaseIds)->get()->keyBy('id');

            $total = (float) Payment::where('payable_type', Purchase::class)
                ->whereBetween('payment_date', [$from, $to])
                ->sum('amount');

            return view('pages.reports.purchase-payments', compact('payments', 'purchases', 'total', 'from', 'to'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport des paiements d\'achat', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    // ── Paiements de vente ────────────────────────────────────────────────────

    public function salePayments(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            $payments = Payment::with(['paymentAccount'])
                ->where('payable_type', Sale::class)
                ->whereBetween('payment_date', [$from, $to])
                ->latest('payment_date')
                ->paginate(30)
                ->withQueryString();

            $saleIds = $payments->pluck('payable_id')->unique();
            $sales   = Sale::with('customer')->whereIn('id', $saleIds)->get()->keyBy('id');

            $total = (float) Payment::where('payable_type', Sale::class)
                ->whereBetween('payment_date', [$from, $to])
                ->sum('amount');

            return view('pages.reports.sale-payments', compact('payments', 'sales', 'total', 'from', 'to'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport des paiements de vente', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    // ── Rapport de dépenses ───────────────────────────────────────────────────

    public function expenses(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            // ── Dépenses d'exploitation
            $byCategory = ExpenseCategory::withSum(
                ['expenses' => fn($q) => $q->whereBetween('expense_date', [$from, $to])], 'amount'
            )
            ->withCount(['expenses' => fn($q) => $q->whereBetween('expense_date', [$from, $to])])
            ->get()
            ->filter(fn($c) => ($c->expenses_sum_amount ?? 0) > 0)
            ->sortByDesc('expenses_sum_amount')
            ->values();

            $uncategorized = (float) Expense::whereNull('expense_category_id')
                ->whereBetween('expense_date', [$from, $to])
                ->sum('amount');

            $totalExpenses = (float) Expense::whereBetween('expense_date', [$from, $to])->sum('amount');

            // ── Achats fournisseurs payés (poste de charge)
            $purchasesBySupplier = Purchase::with('supplier')
                ->where('payment_status', 'paid')
                ->whereBetween('purchase_date', [$from, $to])
                ->select('supplier_id', DB::raw('COUNT(*) as nb'), DB::raw('SUM(amount_paid) as total'))
                ->groupBy('supplier_id')
                ->get()
                ->map(fn($p) => (object)[
                    'name'  => $p->supplier?->name ?? 'Fournisseur inconnu',
                    'nb'    => $p->nb,
                    'total' => (float) $p->total,
                ])
                ->sortByDesc('total')
                ->values();

            $totalPurchases = (float) Purchase::where('payment_status', 'paid')
                ->whereBetween('purchase_date', [$from, $to])
                ->sum('amount_paid');

            $total = $totalExpenses + $totalPurchases;

            // ── Découpage mensuel de la période filtrée
            $monthly = [];
            $cursor  = \Carbon\Carbon::parse($from)->startOfMonth();
            $endDate = \Carbon\Carbon::parse($to);

            while ($cursor->lte($endDate)) {
                $mStart = (string) max($cursor->copy()->startOfMonth()->toDateString(), $from);
                $mEnd   = (string) min($cursor->copy()->endOfMonth()->toDateString(),   $to);
                $monthly[] = [
                    'label'     => $cursor->isoFormat('MMM YY'),
                    'expenses'  => (float) Expense::whereBetween('expense_date', [$mStart, $mEnd])->sum('amount'),
                    'purchases' => (float) Purchase::where('payment_status', 'paid')
                                       ->whereBetween('purchase_date', [$mStart, $mEnd])
                                       ->sum('amount_paid'),
                ];
                $cursor->addMonth();
            }

            return view('pages.reports.expenses', compact(
                'byCategory', 'uncategorized', 'totalExpenses',
                'purchasesBySupplier', 'totalPurchases',
                'total', 'monthly', 'from', 'to'
            ));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport de dépenses', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    // ── Rapport POS ───────────────────────────────────────────────────────────

    public function pos(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            $sessions = PosSession::with(['caisse', 'warehouse', 'user'])
                ->whereDate('opened_at', '>=', $from)
                ->whereDate('opened_at', '<=', $to)
                ->latest('opened_at')
                ->paginate(30)
                ->withQueryString();

            $totalSales = (float) PosSession::whereDate('opened_at', '>=', $from)
                ->whereDate('opened_at', '<=', $to)->sum('total_sales');

            $nbSessions = PosSession::whereDate('opened_at', '>=', $from)
                ->whereDate('opened_at', '<=', $to)->count();

            $avgPerSession = $nbSessions > 0 ? $totalSales / $nbSessions : 0;

            return view('pages.reports.pos', compact(
                'sessions', 'totalSales', 'nbSessions', 'avgPerSession', 'from', 'to'
            ));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport POS', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    // ── Représentants ─────────────────────────────────────────────────────────

    public function agents(Request $request): View|RedirectResponse
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

            return view('pages.reports.agents', compact('agents', 'from', 'to'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du rapport des représentants', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }

    // ── Journal d'activité ────────────────────────────────────────────────────

    public function activity(Request $request): View|RedirectResponse
    {
        try {
            [$from, $to] = $this->dates($request);

            $logs = ActivityLog::with('user')
                ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->latest()
                ->paginate(50)
                ->withQueryString();

            return view('pages.reports.activity', compact('logs', 'from', 'to'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du journal d\'activité', ['filters' => $request->all()]);
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement du rapport.');
        }
    }
}
