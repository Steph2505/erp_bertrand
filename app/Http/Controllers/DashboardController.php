<?php

namespace App\Http\Controllers;

use App\Helpers\FormatHelper;
use App\Helpers\ProfitHelper;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        if (! $request->user()->can('view financial dashboard')) {
            return $this->operationalDashboard();
        }

        try {
            $period = $request->get('period', 'today');
            [$from, $to] = $this->getPeriodDates($period, $request->get('date_from'), $request->get('date_to'));

            $stats      = $this->computeStats($period, $from, $to);
            $chartData  = $this->getChartData($period, $from, $to);
            $chartLabel = $this->getChartLabel($period, $from, $to);

            return view('pages.dashboard.index', array_merge(
                compact('period', 'chartData', 'chartLabel'),
                $stats,
                ['dateFrom' => $from->toDateString(), 'dateTo' => $to->toDateString()]
            ));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du tableau de bord', ['period' => $request->get('period')]);
            throw $e;
        }
    }

    public function apiStats(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'today');
            [$from, $to] = $this->getPeriodDates($period, $request->get('date_from'), $request->get('date_to'));

            $stats      = $this->computeStats($period, $from, $to);
            $chartData  = $this->getChartData($period, $from, $to);
            $chartLabel = $this->getChartLabel($period, $from, $to);

            return response()->json([
                'totalSalesFormatted'     => FormatHelper::money($stats['totalSales']),
                'totalPurchasesFormatted' => FormatHelper::money($stats['totalPurchases']),
                'totalExpensesFormatted'  => FormatHelper::money($stats['totalExpenses']),
                'recetteFormatted'        => FormatHelper::money($stats['recette']),
                'netProfitFormatted'      => FormatHelper::money($stats['netProfit']),
                'netProfitPositive'       => $stats['netProfit'] >= 0,
                'salesTrend'              => round($stats['salesTrend'], 1),
                'salesTrendPositive'      => $stats['salesTrend'] >= 0,
                'chartData'               => $chartData,
                'chartLabel'              => $chartLabel,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement des statistiques du tableau de bord', ['period' => $request->get('period')]);
            return response()->json(['message' => 'Impossible de charger les statistiques.'], 500);
        }
    }

    public function apiCardDetail(Request $request): JsonResponse
    {
        try {
            $card   = $request->get('card', 'sales');
            $period = $request->get('period', 'today');
            [$from, $to] = $this->getPeriodDates($period, $request->get('date_from'), $request->get('date_to'));
            $page    = $request->integer('page', 1);
            $perPage = 15;

            $statusMap = [
                'paid'      => ['Payé', 'green'],
                'partial'   => ['Partiel', 'yellow'],
                'pending'   => ['En attente', 'yellow'],
                'draft'     => ['Brouillon', 'gray'],
                'confirmed' => ['Confirmé', 'green'],
                'completed' => ['Terminé', 'green'],
            ];

            switch ($card) {
                case 'purchases':
                    $paginator = Purchase::with('supplier')
                        ->whereBetween('purchase_date', [$from, $to])
                        ->latest('purchase_date')->latest('id')
                        ->paginate($perPage, ['*'], 'page', $page);

                    $data = $paginator->getCollection()->map(function ($p) use ($statusMap) {
                        [$label, $color] = $statusMap[$p->payment_status] ?? [ucfirst($p->payment_status), 'gray'];
                        return [
                            'reference'    => $p->reference,
                            'party'        => $p->supplier?->name ?? '—',
                            'date'         => FormatHelper::date($p->purchase_date),
                            'total'        => FormatHelper::money($p->total),
                            'status_label' => $label,
                            'status_color' => $color,
                            'show_url'     => route('purchases.show', $p->id),
                        ];
                    });
                    break;

                case 'expenses':
                    $paginator = Expense::with('category')
                        ->whereBetween('expense_date', [$from, $to])
                        ->latest('expense_date')->latest('id')
                        ->paginate($perPage, ['*'], 'page', $page);

                    $data = $paginator->getCollection()->map(fn($e) => [
                        'reference'   => $e->reference,
                        'party'       => $e->category?->name ?? '—',
                        'date'        => FormatHelper::date($e->expense_date),
                        'total'       => FormatHelper::money($e->amount),
                        'description' => $e->description,
                    ]);
                    break;

                case 'recette':
                    $methodLabels = [
                        'cash'          => 'Espèces',
                        'mobile_money'  => 'Mobile Money',
                        'card'          => 'Carte',
                        'bank_transfer' => 'Virement',
                        'cheque'        => 'Chèque',
                    ];

                    $paginator = Payment::with('payable')
                        ->where('payable_type', Sale::class)
                        ->whereBetween('payment_date', [$from, $to])
                        ->latest('payment_date')->latest('id')
                        ->paginate($perPage, ['*'], 'page', $page);

                    $data = $paginator->getCollection()->map(fn($pay) => [
                        'reference'   => $pay->reference,
                        'party'       => $pay->payable?->reference ?? '—',
                        'date'        => FormatHelper::date($pay->payment_date),
                        'total'       => FormatHelper::money($pay->amount),
                        'description' => $methodLabels[$pay->payment_method] ?? ucfirst($pay->payment_method),
                    ]);
                    break;

                case 'profit':
                case 'sales':
                default:
                    $paginator = Sale::with(['customer', 'items'])
                        ->whereIn('status', ['confirmed', 'completed'])
                        ->whereBetween('sale_date', [$from, $to])
                        ->latest('sale_date')->latest('id')
                        ->paginate($perPage, ['*'], 'page', $page);

                    $data = $paginator->getCollection()->map(function ($s) use ($statusMap, $card) {
                        [$label, $color] = $statusMap[$s->payment_status] ?? [ucfirst($s->payment_status), 'gray'];
                        $row = [
                            'reference'    => $s->reference,
                            'party'        => $s->customer?->name ?? 'Client comptoir',
                            'date'         => FormatHelper::date($s->sale_date),
                            'total'        => FormatHelper::money($s->total),
                            'status_label' => $label,
                            'status_color' => $color,
                            'origin_label' => $s->is_pos ? 'POS' : 'Direct',
                            'show_url'     => route('pos.receipt', $s->id),
                        ];

                        if ($card === 'profit') {
                            $cost = $s->items->sum(fn($i) => $i->item_type === 'pack'
                                ? $i->quantity * $i->unit_cost
                                : $i->quantity * $i->units_per_item * $i->unit_cost);
                            $row['profit'] = FormatHelper::money($s->total - $cost);
                        }

                        return $row;
                    });
                    break;
            }

            return response()->json([
                'data'         => $data,
                'card'         => $card,
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem() ?? 0,
                'to'           => $paginator->lastItem() ?? 0,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement du détail de carte', ['card' => $request->get('card')]);
            return response()->json(['message' => 'Impossible de charger le détail.'], 500);
        }
    }

    // Vue de repli pour les utilisateurs sans la permission "view financial
    // dashboard" : aucun montant, uniquement des compteurs et des raccourcis
    // vers les sections auxquelles l'utilisateur a droit.
    private function operationalDashboard(): View
    {
        $user = auth()->user();

        $todaySalesCount = Sale::whereIn('status', ['confirmed', 'completed'])
            ->whereDate('sale_date', now()->toDateString())
            ->count();

        $lowStockProducts = Product::where('is_active', true)
            ->whereRaw('stock_quantity <= min_stock_quantity')
            ->orderBy('stock_quantity')
            ->limit(10)
            ->get();

        $quickLinks = collect([
            ['label' => 'Point de vente (POS)', 'route' => 'pos.index',           'permission' => 'manage pos',       'icon' => 'currency-dollar'],
            ['label' => 'État de stock',        'route' => 'stock-status.index',  'permission' => 'manage stock',     'icon' => 'chart-bar'],
            ['label' => 'Achats',               'route' => 'purchases.index',     'permission' => 'manage purchases', 'icon' => 'shopping-cart'],
            ['label' => 'Articles',             'route' => 'products.index',      'permission' => 'manage products',  'icon' => 'cube'],
            ['label' => 'Clients',              'route' => 'customers.index',     'permission' => 'manage customers', 'icon' => 'identification'],
            ['label' => 'Fournisseurs',         'route' => 'suppliers.index',     'permission' => 'manage suppliers', 'icon' => 'identification'],
        ])->filter(fn($link) => $user->can($link['permission']))->values();

        return view('pages.dashboard.operational', compact('todaySalesCount', 'lowStockProducts', 'quickLinks'));
    }

    // ─── Helpers privés ───────────────────────────────────────────────────────

    private function getPeriodDates(string $period, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        if ($period === 'custom' && $dateFrom && $dateTo) {
            try {
                $from = \Carbon\Carbon::parse($dateFrom)->startOfDay();
                $to   = \Carbon\Carbon::parse($dateTo)->endOfDay();
                if ($from->lte($to)) {
                    return [$from, $to];
                }
            } catch (\Exception $e) {
                // dates invalides → on retombe sur "aujourd'hui" ci-dessous
            }
        }

        return [now()->startOfDay(), now()->endOfDay()];
    }

    private function getChartLabel(string $period, \Carbon\Carbon $from, \Carbon\Carbon $to): string
    {
        if ($period === 'custom') {
            return $from->isSameDay($to)
                ? 'Le ' . $from->translatedFormat('d/m/Y')
                : 'Du ' . $from->translatedFormat('d/m/Y') . ' au ' . $to->translatedFormat('d/m/Y');
        }

        return "Aujourd'hui (par heure)";
    }

    private function computeStats(string $period, $from, $to): array
    {
        $totalSales     = (float) Sale::whereIn('status', ['confirmed', 'completed'])
            ->whereBetween('sale_date', [$from, $to])
            ->sum('total');
        $totalPurchases = (float) Purchase::whereBetween('purchase_date', [$from, $to])->sum('total');
        $totalExpenses  = (float) Expense::whereBetween('expense_date', [$from, $to])->sum('amount');

        // Recette = encaissements réels (paiements reçus sur les ventes), distinct
        // de "Ventes" qui peut inclure du crédit non encore payé.
        $recette = (float) Payment::where('payable_type', Sale::class)
            ->whereBetween('payment_date', [$from, $to])
            ->sum('amount');

        // Bénéfice net = CA - coût réel des produits vendus (COGS) - charges,
        // même formule que le rapport Profit/Perte (ProfitHelper::productCogs)
        // pour ne jamais afficher deux chiffres de bénéfice différents.
        $cogs      = ProfitHelper::productCogs($from->toDateString(), $to->toDateString());
        $netProfit = $totalSales - $cogs - $totalExpenses;

        // Tendance vs période précédente (même durée, immédiatement avant $from)
        $days     = $from->diffInDays($to) + 1;
        $prevTo   = $from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();
        $prevSales  = Sale::whereIn('status', ['confirmed', 'completed'])
            ->whereBetween('sale_date', [$prevFrom, $prevTo])
            ->sum('total');
        $salesTrend = $prevSales > 0 ? (($totalSales - $prevSales) / $prevSales) * 100 : 0;

        return compact('totalSales', 'totalPurchases', 'totalExpenses', 'recette', 'netProfit', 'salesTrend');
    }

    private function getChartData(string $period, \Carbon\Carbon $from, \Carbon\Carbon $to): array
    {
        $labels = $salesData = $purchasesData = $recetteData = $expensesData = $profitData = [];

        if ($period === 'today') {
            for ($h = 0; $h < 24; $h++) {
                $labels[]        = str_pad($h, 2, '0', STR_PAD_LEFT) . 'h';
                $sales           = (float) Sale::whereIn('status', ['confirmed', 'completed'])
                    ->whereDate('sale_date', now()->toDateString())
                    ->whereRaw('HOUR(created_at) = ?', [$h])->sum('total');
                $purchasesData[] = (float) Purchase::whereIn('status', ['confirmed'])
                    ->whereDate('purchase_date', now()->toDateString())
                    ->whereRaw('HOUR(created_at) = ?', [$h])->sum('total');
                $recette         = (float) Payment::where('payable_type', Sale::class)
                    ->whereDate('payment_date', now()->toDateString())
                    ->whereRaw('HOUR(created_at) = ?', [$h])->sum('amount');
                $expenses        = (float) Expense::whereDate('expense_date', now()->toDateString())
                    ->whereRaw('HOUR(created_at) = ?', [$h])->sum('amount');
                $cogs            = (float) DB::table('sale_items')
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->whereIn('sales.status', ['confirmed', 'completed'])
                    ->whereDate('sales.sale_date', now()->toDateString())
                    ->whereRaw('HOUR(sales.created_at) = ?', [$h])
                    ->selectRaw("COALESCE(SUM(
                        CASE
                            WHEN sale_items.item_type = 'pack' THEN sale_items.quantity * sale_items.unit_cost
                            ELSE sale_items.quantity * sale_items.units_per_item * sale_items.unit_cost
                        END
                    ), 0) as total")->value('total');

                $salesData[]    = $sales;
                $recetteData[]  = $recette;
                $expensesData[] = $expenses;
                $profitData[]   = $sales - $cogs - $expenses;
            }
        } else {
            // Période personnalisée : une donnée par jour, quelle que soit la durée
            $day = $from->copy();
            while ($day->lte($to)) {
                $labels[]        = $day->format('d/m');
                $sales           = (float) Sale::whereIn('status', ['confirmed', 'completed'])
                    ->whereDate('sale_date', $day->toDateString())->sum('total');
                $purchasesData[] = (float) Purchase::whereIn('status', ['confirmed'])
                    ->whereDate('purchase_date', $day->toDateString())->sum('total');
                $recette         = (float) Payment::where('payable_type', Sale::class)
                    ->whereDate('payment_date', $day->toDateString())->sum('amount');
                $expenses        = (float) Expense::whereDate('expense_date', $day->toDateString())->sum('amount');
                $cogs            = ProfitHelper::productCogs($day->toDateString(), $day->toDateString());

                $salesData[]    = $sales;
                $recetteData[]  = $recette;
                $expensesData[] = $expenses;
                $profitData[]   = $sales - $cogs - $expenses;
                $day->addDay();
            }
        }

        return compact('labels', 'salesData', 'purchasesData', 'recetteData', 'expensesData', 'profitData');
    }
}
