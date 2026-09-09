@extends('layouts.app')
@section('title', 'Tableau de bord')

@section('content')
<div x-data="dashboard()" x-init="init()">

<div class="page-header">
    <div class="page-header__title">
        <h2>Tableau de bord</h2>
        <p>Vue d'ensemble de votre activité</p>
    </div>
    <div class="page-header__actions">
        <div class="filter-tabs">
            <button @click="setPeriod('today')"  class="filter-tabs__btn" :class="{ 'filter-tabs__btn--active': period === 'today'  }">Aujourd'hui</button>
            <button @click="setPeriod('week')"   class="filter-tabs__btn" :class="{ 'filter-tabs__btn--active': period === 'week'   }">Semaine</button>
            <button @click="setPeriod('month')"  class="filter-tabs__btn" :class="{ 'filter-tabs__btn--active': period === 'month'  }">Mois</button>
            <button @click="setPeriod('year')"   class="filter-tabs__btn" :class="{ 'filter-tabs__btn--active': period === 'year'   }">Année</button>
        </div>
    </div>
</div>

{{-- KPI Cards --}}
<div class="stat-grid">
    {{-- Ventes --}}
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Ventes</div>
            <div class="stat-card__value" x-text="loading ? '…' : stats.totalSalesFormatted">{{ \App\Helpers\FormatHelper::money($totalSales) }}</div>
            <div class="stat-card__trend" :class="stats.salesTrendPositive ? 'stat-card__trend--up' : 'stat-card__trend--down'">
                <template x-if="!loading">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            :d="stats.salesTrendPositive
                                ? 'M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941'
                                : 'M2.25 6 9 12.75l4.286-4.286a11.948 11.948 0 0 1 4.306 6.43l.776 2.898m0 0 3.182-5.511m-3.182 5.51-5.511-3.181'"/>
                    </svg>
                </template>
                <span x-text="loading ? '' : (Math.abs(stats.salesTrend).toFixed(1) + '% vs période préc.')">
                    {{ number_format(abs($salesTrend), 1) }}% vs période préc.
                </span>
            </div>
        </div>
        <div class="stat-card__icon stat-card__icon--green">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        </div>
    </div>

    {{-- Achats --}}
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Achats</div>
            <div class="stat-card__value" x-text="loading ? '…' : stats.totalPurchasesFormatted">{{ \App\Helpers\FormatHelper::money($totalPurchases) }}</div>
            <div class="stat-card__trend stat-card__trend--flat">Coût d'approvisionnement</div>
        </div>
        <div class="stat-card__icon stat-card__icon--blue">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
        </div>
    </div>

    {{-- Bénéfice net --}}
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Bénéfice net</div>
            <div class="stat-card__value"
                 :style="!loading && !stats.netProfitPositive ? 'color:#EF4444' : 'color:#22C55E'"
                 x-text="loading ? '…' : stats.netProfitFormatted">
                {{ \App\Helpers\FormatHelper::money($netProfit) }}
            </div>
            <div class="stat-card__trend" :class="stats.netProfitPositive ? 'stat-card__trend--up' : 'stat-card__trend--down'">
                Ventes – Achats – Dépenses
            </div>
        </div>
        <div class="stat-card__icon" :class="stats.netProfitPositive ? 'stat-card__icon--green' : 'stat-card__icon--red'">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/></svg>
        </div>
    </div>

    {{-- Alertes stock --}}
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Alertes stock</div>
            <div class="stat-card__value" x-text="loading ? '…' : stats.lowStockCount">{{ $lowStockCount }}</div>
            <div class="stat-card__trend" :class="stats.lowStockCount > 0 ? 'stat-card__trend--down' : 'stat-card__trend--up'">
                <span x-text="stats.lowStockCount > 0 ? 'Produits en stock faible' : 'Stocks OK'">
                    {{ $lowStockCount > 0 ? 'Produits en stock faible' : 'Stocks OK' }}
                </span>
            </div>
        </div>
        <div class="stat-card__icon stat-card__icon--yellow">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
        </div>
    </div>
</div>

{{-- Graphiques --}}
<div class="dashboard__charts">
    <div class="dashboard__chart-card">
        <h3>Évolution Ventes / Achats — <span x-text="stats.chartLabel">
            @switch($period)
                @case('today')  Aujourd'hui (par heure) @break
                @case('week')   Cette semaine (par jour) @break
                @case('month')  Ce mois (par jour) @break
                @case('year')   Cette année (par mois) @break
            @endswitch
        </span></h3>
        <div style="position:relative;min-height:200px;">
            <div x-show="loading" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#64748B;font-size:13px;">
                Chargement...
            </div>
            <canvas id="salesChart" x-show="!loading"></canvas>
        </div>
    </div>

    <div class="dashboard">
        <div class="dashboard__alerts">
            <div class="dashboard__alerts-header">
                <h3>⚠️ Stock faible</h3>
                <a href="{{ route('reports.stock') }}" class="dashboard__alerts-link">Voir tout</a>
            </div>
            @forelse($lowStockProducts as $product)
                <div class="dashboard__alerts-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                    <span class="dashboard__alerts-item-name">{{ $product->display_name }}</span>
                    <span class="dashboard__alerts-item-stock">{{ $product->stock_quantity }} {{ $product->unit?->abbreviation ?? 'u.' }}</span>
                </div>
            @empty
                <div class="dashboard__empty">Tous les stocks sont suffisants ✓</div>
            @endforelse
        </div>
    </div>
</div>

{{-- Tables --}}
<div class="dashboard__tables">
    {{-- Dernières ventes --}}
    <div class="table-wrapper">
        <div class="table-wrapper__header">
            <strong class="table-wrapper__title">Dernières ventes</strong>
            <a href="{{ route('sales.index') }}" class="btn btn--ghost btn--sm">Voir tout</a>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Client</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <template x-if="loading">
                    <tr><td colspan="5" class="cell-empty">Chargement...</td></tr>
                </template>
                <template x-if="!loading && stats.recentSales.length === 0">
                    <tr><td colspan="5" class="cell-empty">Aucune vente</td></tr>
                </template>
                <template x-for="sale in stats.recentSales" :key="sale.id">
                    <tr>
                        <td><a :href="'/sales/' + sale.id" class="cell-reference" x-text="sale.reference"></a></td>
                        <td x-text="sale.customer"></td>
                        <td x-text="sale.sale_date"></td>
                        <td><strong x-text="sale.total"></strong></td>
                        <td><span class="badge" :class="'badge--' + sale.status_color" x-text="sale.status_label"></span></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    {{-- Top produits --}}
    <div class="table-wrapper">
        <div class="table-wrapper__header">
            <strong class="table-wrapper__title">Top produits vendus</strong>
            <a href="{{ route('reports.trending') }}" class="btn btn--ghost btn--sm">Rapport</a>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Produit / Pack</th>
                    <th>Unités vendues</th>
                    <th>Chiffre d'affaires</th>
                </tr>
            </thead>
            <tbody>
                <template x-if="loading">
                    <tr><td colspan="4" class="cell-empty">Chargement...</td></tr>
                </template>
                <template x-if="!loading && stats.topProducts.length === 0">
                    <tr><td colspan="4" class="cell-empty">Aucune donnée sur cette période</td></tr>
                </template>
                <template x-for="(item, i) in stats.topProducts" :key="i">
                    <tr>
                        <td class="cell-rank" x-text="'#' + (i + 1)"></td>
                        <td x-text="item.item_name"></td>
                        <td x-text="item.total_units"></td>
                        <td><strong x-text="item.total_revenue"></strong></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>

</div>{{-- /x-data --}}
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
let salesChartInstance = null;

function dashboard() {
    return {
        period: '{{ $period }}',
        loading: false,

        stats: {
            totalSalesFormatted:     '{{ \App\Helpers\FormatHelper::money($totalSales) }}',
            totalPurchasesFormatted: '{{ \App\Helpers\FormatHelper::money($totalPurchases) }}',
            totalExpensesFormatted:  '{{ \App\Helpers\FormatHelper::money($totalExpenses) }}',
            netProfitFormatted:      '{{ \App\Helpers\FormatHelper::money($netProfit) }}',
            netProfitPositive:       {{ $netProfit >= 0 ? 'true' : 'false' }},
            lowStockCount:           {{ $lowStockCount }},
            salesTrend:              {{ round($salesTrend, 1) }},
            salesTrendPositive:      {{ $salesTrend >= 0 ? 'true' : 'false' }},
            chartLabel:              '{{ match($period) { 'today' => "Aujourd\'hui (par heure)", 'week' => 'Cette semaine (par jour)', 'month' => 'Ce mois (par jour)', 'year' => 'Cette année (par mois)', default => '' } }}',
            topProducts:             @json($topProducts),
            recentSales:             @json($recentSales),
        },

        chartData: @json($chartData),

        init() {
            this.$nextTick(() => this.buildChart());
        },

        async setPeriod(p) {
            if (this.period === p || this.loading) return;
            this.period = p;
            this.loading = true;

            try {
                const res  = await fetch(`{{ route('dashboard.stats') }}?period=${p}`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                });
                const data = await res.json();

                this.stats     = { ...data };
                this.chartData = data.chartData;

                this.$nextTick(() => this.buildChart());
            } catch (e) {
                console.error('Dashboard stats error:', e);
            } finally {
                this.loading = false;
            }
        },

        buildChart() {
            const canvas = document.getElementById('salesChart');
            if (!canvas) return;

            if (salesChartInstance) {
                salesChartInstance.destroy();
                salesChartInstance = null;
            }

            salesChartInstance = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: this.chartData.labels,
                    datasets: [
                        {
                            label: 'Ventes',
                            data: this.chartData.salesData,
                            backgroundColor: 'rgba(76,187,23,0.75)',
                            borderRadius: 6,
                            borderSkipped: false,
                        },
                        {
                            label: 'Achats',
                            data: this.chartData.purchasesData,
                            backgroundColor: 'rgba(59,130,246,0.65)',
                            borderRadius: 6,
                            borderSkipped: false,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: ctx => ctx.dataset.label + ' : ' + new Intl.NumberFormat('fr-FR').format(ctx.parsed.y) + ' ' + window.CURRENCY
                            }
                        }
                    },
                    scales: {
                        y: {
                            ticks: { callback: v => new Intl.NumberFormat('fr-FR', { notation: 'compact' }).format(v) + ' ' + window.CURRENCY }
                        }
                    }
                }
            });
        }
    };
}
</script>
@endpush
