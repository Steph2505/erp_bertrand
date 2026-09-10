<?php

namespace App\Http\Controllers;

use App\Helpers\FormatHelper;
use App\Models\Expense;
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
        try {
            $period = $request->get('period', 'today');
            [$from, $to] = $this->getPeriodDates($period);

            $stats       = $this->computeStats($period, $from, $to);
            $chartData   = $this->getChartData($period, $from, $to);
            $recentSales = $this->getRecentSales();
            $topProducts = $this->getTopProducts($from, $to);

            $lowStockProducts = Product::where('is_active', true)
                ->whereRaw('stock_quantity <= min_stock_quantity')
                ->orderBy('stock_quantity')
                ->limit(10)
                ->get();

            return view('pages.dashboard.index', array_merge(
                compact('period', 'chartData', 'lowStockProducts', 'recentSales', 'topProducts'),
                $stats
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
            [$from, $to] = $this->getPeriodDates($period);

            $stats     = $this->computeStats($period, $from, $to);
            $chartData = $this->getChartData($period, $from, $to);

            $topProducts = $this->getTopProducts($from, $to);
            $recentSales = $this->getRecentSales();

            $chartLabel = match($period) {
                'today' => "Aujourd'hui (par heure)",
                'week'  => 'Cette semaine (par jour)',
                'month' => 'Ce mois (par jour)',
                'year'  => 'Cette année (par mois)',
                default => '',
            };

            return response()->json([
                'totalSalesFormatted'     => FormatHelper::money($stats['totalSales']),
                'totalPurchasesFormatted' => FormatHelper::money($stats['totalPurchases']),
                'totalExpensesFormatted'  => FormatHelper::money($stats['totalExpenses']),
                'netProfitFormatted'      => FormatHelper::money($stats['netProfit']),
                'netProfitPositive'       => $stats['netProfit'] >= 0,
                'lowStockCount'           => $stats['lowStockCount'],
                'salesTrend'              => round($stats['salesTrend'], 1),
                'salesTrendPositive'      => $stats['salesTrend'] >= 0,
                'chartData'               => $chartData,
                'chartLabel'              => $chartLabel,
                'topProducts'             => $topProducts,
                'recentSales'             => $recentSales,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement des statistiques du tableau de bord', ['period' => $request->get('period')]);
            return response()->json(['message' => 'Impossible de charger les statistiques.'], 500);
        }
    }

    // ─── Helpers privés ───────────────────────────────────────────────────────

    private function getPeriodDates(string $period): array
    {
        return match($period) {
            'week'  => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'year'  => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };
    }

    private function computeStats(string $period, $from, $to): array
    {
        $totalSales     = Sale::whereBetween('sale_date', [$from, $to])->sum('total');
        $totalPurchases = Purchase::whereBetween('purchase_date', [$from, $to])->sum('total');
        $totalExpenses  = Expense::whereBetween('expense_date', [$from, $to])->sum('amount');
        $netProfit      = $totalSales - $totalPurchases - $totalExpenses;

        $lowStockCount  = Product::where('is_active', true)
            ->whereRaw('stock_quantity <= min_stock_quantity')
            ->count();

        // Tendance vs période précédente
        [$prevFrom, $prevTo] = match($period) {
            'week'  => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'year'  => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            default => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
        };
        $prevSales  = Sale::whereBetween('sale_date', [$prevFrom, $prevTo])->sum('total');
        $salesTrend = $prevSales > 0 ? (($totalSales - $prevSales) / $prevSales) * 100 : 0;

        return compact('totalSales', 'totalPurchases', 'totalExpenses', 'netProfit', 'lowStockCount', 'salesTrend');
    }

    private function getTopProducts($from, $to): array
    {
        return DB::table('sale_items')
            ->selectRaw('sale_items.item_name, SUM(sale_items.quantity) as total_units, SUM(sale_items.subtotal) as total_revenue')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereIn('sales.status', ['confirmed', 'completed'])
            ->whereBetween('sales.sale_date', [$from, $to])
            ->whereNotNull('sale_items.item_name')
            ->groupByRaw('sale_items.item_name')
            ->orderByDesc('total_units')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'item_name'     => $r->item_name,
                'total_units'   => (int) $r->total_units,
                'total_revenue' => FormatHelper::money($r->total_revenue),
            ])
            ->toArray();
    }

    private function getRecentSales(): array
    {
        $statusMap = [
            'paid'     => ['Payé',          'green'],
            'partial'  => ['Partiel',        'yellow'],
            'pending'  => ['En attente',     'yellow'],
            'draft'    => ['Brouillon',      'gray'],
            'confirmed'=> ['Confirmé',       'green'],
        ];

        return Sale::with('customer')
            ->latest('sale_date')
            ->limit(5)
            ->get()
            ->map(function ($s) use ($statusMap) {
                [$label, $color] = $statusMap[$s->payment_status] ?? [ucfirst($s->payment_status), 'gray'];
                return [
                    'id'             => $s->id,
                    'reference'      => $s->reference,
                    'customer'       => $s->customer?->name ?? 'Client comptoir',
                    'sale_date'      => FormatHelper::date($s->sale_date),
                    'total'          => FormatHelper::money($s->total),
                    'status_label'   => $label,
                    'status_color'   => $color,
                ];
            })
            ->toArray();
    }

    private function getChartData(string $period, \Carbon\Carbon $from, \Carbon\Carbon $to): array
    {
        $labels = $salesData = $purchasesData = [];

        switch ($period) {
            case 'today':
                for ($h = 0; $h < 24; $h++) {
                    $labels[]        = str_pad($h, 2, '0', STR_PAD_LEFT) . 'h';
                    $salesData[]     = (float) Sale::whereIn('status', ['confirmed', 'completed'])
                        ->whereDate('sale_date', now()->toDateString())
                        ->whereRaw('HOUR(created_at) = ?', [$h])->sum('total');
                    $purchasesData[] = (float) Purchase::whereIn('status', ['confirmed'])
                        ->whereDate('purchase_date', now()->toDateString())
                        ->whereRaw('HOUR(created_at) = ?', [$h])->sum('total');
                }
                break;

            case 'week':
                $day = $from->copy();
                while ($day->lte($to)) {
                    $labels[]        = $day->translatedFormat('D d');
                    $salesData[]     = (float) Sale::whereIn('status', ['confirmed', 'completed'])
                        ->whereDate('sale_date', $day->toDateString())->sum('total');
                    $purchasesData[] = (float) Purchase::whereIn('status', ['confirmed'])
                        ->whereDate('purchase_date', $day->toDateString())->sum('total');
                    $day->addDay();
                }
                break;

            case 'month':
                $day = $from->copy();
                while ($day->lte($to)) {
                    $labels[]        = $day->format('d');
                    $salesData[]     = (float) Sale::whereIn('status', ['confirmed', 'completed'])
                        ->whereDate('sale_date', $day->toDateString())->sum('total');
                    $purchasesData[] = (float) Purchase::whereIn('status', ['confirmed'])
                        ->whereDate('purchase_date', $day->toDateString())->sum('total');
                    $day->addDay();
                }
                break;

            case 'year':
                for ($m = 1; $m <= 12; $m++) {
                    $labels[]        = now()->setMonth($m)->translatedFormat('M');
                    $salesData[]     = (float) Sale::whereIn('status', ['confirmed', 'completed'])
                        ->whereYear('sale_date', $from->year)->whereMonth('sale_date', $m)->sum('total');
                    $purchasesData[] = (float) Purchase::whereIn('status', ['confirmed'])
                        ->whereYear('purchase_date', $from->year)->whereMonth('purchase_date', $m)->sum('total');
                }
                break;
        }

        return compact('labels', 'salesData', 'purchasesData');
    }
}
