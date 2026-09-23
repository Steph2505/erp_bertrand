@extends('layouts.app')
@section('title', 'Tableau de bord')

@section('content')
<div x-data="dashboard()" x-init="init()">

<div class="page-header">
    <div class="page-header__title">
        <h2>Tableau de bord</h2>
        <p>Vue d'ensemble de votre activité</p>
    </div>
    <div class="page-header__actions" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="filter-tabs">
            <button @click="setToday()" class="filter-tabs__btn" :class="{ 'filter-tabs__btn--active': period === 'today' }">Aujourd'hui</button>
        </div>
        <div class="form-group" style="margin:0"><label>Du</label><input type="date" x-model="dateFrom" @change="setCustom()" class="form-control"></div>
        <div class="form-group" style="margin:0"><label>Au</label><input type="date" x-model="dateTo" @change="setCustom()" class="form-control"></div>
    </div>
</div>

{{-- KPI Cards --}}
<div class="stat-grid">
    {{-- Ventes --}}
    <div class="stat-card" style="cursor:pointer" @click="openModal('sales', 'Ventes')" title="Voir le détail des ventes">
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

    {{-- Recette (encaissements réels) --}}
    <div class="stat-card" style="cursor:pointer" @click="openModal('recette', 'Recette (encaissements)')" title="Voir le détail des encaissements">
        <div class="stat-card__info">
            <div class="stat-card__label">Recette</div>
            <div class="stat-card__value" x-text="loading ? '…' : stats.recetteFormatted">{{ \App\Helpers\FormatHelper::money($recette) }}</div>
            <div class="stat-card__trend stat-card__trend--flat">Encaissements réels</div>
        </div>
        <div class="stat-card__icon stat-card__icon--green">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3M3.75 6h16.5a1.5 1.5 0 0 1 1.5 1.5v9a1.5 1.5 0 0 1-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5v-9a1.5 1.5 0 0 1 1.5-1.5Z"/></svg>
        </div>
    </div>

    {{-- Bénéfice net --}}
    <div class="stat-card" style="cursor:pointer" @click="openModal('profit', 'Bénéfice net (marge par vente)')" title="Voir le détail par vente">
        <div class="stat-card__info">
            <div class="stat-card__label">Bénéfice net</div>
            <div class="stat-card__value" x-text="loading ? '…' : stats.netProfitFormatted">{{ \App\Helpers\FormatHelper::money($netProfit) }}</div>
            <div class="stat-card__trend" :class="stats.netProfitPositive ? 'stat-card__trend--up' : 'stat-card__trend--down'">
                <span x-text="loading ? '' : (stats.netProfitPositive ? 'Ventes − coût des ventes − charges' : 'Perte sur la période')">
                    {{ $netProfit >= 0 ? 'Ventes − coût des ventes − charges' : 'Perte sur la période' }}
                </span>
            </div>
        </div>
        <div class="stat-card__icon {{ $netProfit >= 0 ? 'stat-card__icon--green' : 'stat-card__icon--red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/></svg>
        </div>
    </div>

    {{-- Achats --}}
    <div class="stat-card" style="cursor:pointer" @click="openModal('purchases', 'Achats')" title="Voir le détail des achats">
        <div class="stat-card__info">
            <div class="stat-card__label">Achats</div>
            <div class="stat-card__value" x-text="loading ? '…' : stats.totalPurchasesFormatted">{{ \App\Helpers\FormatHelper::money($totalPurchases) }}</div>
            <div class="stat-card__trend stat-card__trend--flat">Coût d'approvisionnement</div>
        </div>
        <div class="stat-card__icon stat-card__icon--blue">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
        </div>
    </div>

    {{-- Dépenses --}}
    <div class="stat-card" style="cursor:pointer" @click="openModal('expenses', 'Dépenses')" title="Voir le détail des dépenses">
        <div class="stat-card__info">
            <div class="stat-card__label">Dépenses</div>
            <div class="stat-card__value" x-text="loading ? '…' : stats.totalExpensesFormatted">{{ \App\Helpers\FormatHelper::money($totalExpenses) }}</div>
            <div class="stat-card__trend stat-card__trend--flat">Charges de la période</div>
        </div>
        <div class="stat-card__icon stat-card__icon--red">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6 9 12.75l4.286-4.286a11.948 11.948 0 0 1 4.306 6.43l.776 2.898m0 0 3.182-5.511m-3.182 5.51-5.511-3.181"/></svg>
        </div>
    </div>
</div>

{{-- Graphiques --}}
<div class="dashboard__charts">
    <div class="dashboard__chart-card">
        <h3>Évolution Ventes / Achats / Recette / Bénéfice / Dépenses — <span x-text="stats.chartLabel">{{ $chartLabel }}</span></h3>
        <div style="position:relative;min-height:200px;">
            <div x-show="loading" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#64748B;font-size:13px;">
                Chargement...
            </div>
            <canvas id="salesChart" x-show="!loading"></canvas>
        </div>
    </div>
</div>

{{-- Modale détail carte --}}
<div class="modal-overlay" x-show="modalOpen" x-cloak @click.self="closeModal()" x-transition>
    <div class="modal modal--xl">
        <div class="modal__header">
            <strong x-text="modalTitle"></strong>
            <button class="modal__close" @click="closeModal()">&times;</button>
        </div>
        <div class="modal__body" style="padding:0;">
            <div style="position:relative;min-height:200px;">
                <div x-show="modalLoading" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
                    <svg style="width:28px;height:28px;color:#1749B3;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th x-text="modalPartyLabel"></th>
                            <th>Date</th>
                            <template x-if="modalCard === 'sales'"><th>Origine</th></template>
                            <th style="text-align:right">Montant</th>
                            <template x-if="modalCard === 'profit'"><th style="text-align:right">Marge</th></template>
                            <template x-if="modalCard === 'sales' || modalCard === 'purchases'"><th>Statut</th></template>
                            <template x-if="modalCard === 'expenses' || modalCard === 'recette'"><th>Détail</th></template>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="!modalLoading && modalRows.length === 0">
                            <tr><td colspan="7" class="cell-empty">Aucun élément sur cette période</td></tr>
                        </template>
                        <template x-for="row in modalRows" :key="row.reference">
                            <tr>
                                <td>
                                    <template x-if="row.show_url"><a :href="row.show_url" class="cell-reference" x-text="row.reference"></a></template>
                                    <template x-if="!row.show_url"><span x-text="row.reference"></span></template>
                                </td>
                                <td x-text="row.party"></td>
                                <td x-text="row.date"></td>
                                <template x-if="modalCard === 'sales'"><td><span class="badge" :class="row.origin_label === 'POS' ? 'badge--blue' : 'badge--gray'" x-text="row.origin_label"></span></td></template>
                                <td style="text-align:right"><strong x-text="row.total"></strong></td>
                                <template x-if="modalCard === 'profit'"><td style="text-align:right" x-text="row.profit"></td></template>
                                <template x-if="modalCard === 'sales' || modalCard === 'purchases'">
                                    <td><span class="badge" :class="'badge--' + row.status_color" x-text="row.status_label"></span></td>
                                </template>
                                <template x-if="modalCard === 'expenses' || modalCard === 'recette'"><td x-text="row.description"></td></template>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="table-wrapper__footer">
                <span x-text="modalFrom + '–' + modalTo + ' sur ' + modalTotal"></span>
                <div style="display:flex;gap:4px;" x-show="modalLastPage > 1">
                    <button @click="modalGoTo(modalCurrentPage-1)" :disabled="modalCurrentPage<=1||modalLoading" class="btn btn--ghost btn--sm btn--icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg></button>
                    <button @click="modalGoTo(modalCurrentPage+1)" :disabled="modalCurrentPage>=modalLastPage||modalLoading" class="btn btn--ghost btn--sm btn--icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg></button>
                </div>
            </div>
        </div>
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
        dateFrom: '{{ $dateFrom }}',
        dateTo: '{{ $dateTo }}',
        loading: false,

        modalOpen: false,
        modalCard: '',
        modalTitle: '',
        modalRows: [],
        modalTotal: 0,
        modalFrom: 0,
        modalTo: 0,
        modalCurrentPage: 1,
        modalLastPage: 1,
        modalLoading: false,

        stats: {
            totalSalesFormatted:        '{{ \App\Helpers\FormatHelper::money($totalSales) }}',
            totalPurchasesFormatted:    '{{ \App\Helpers\FormatHelper::money($totalPurchases) }}',
            totalExpensesFormatted:     '{{ \App\Helpers\FormatHelper::money($totalExpenses) }}',
            recetteFormatted:           '{{ \App\Helpers\FormatHelper::money($recette) }}',
            netProfitFormatted:         '{{ \App\Helpers\FormatHelper::money($netProfit) }}',
            netProfitPositive:          {{ $netProfit >= 0 ? 'true' : 'false' }},
            salesTrend:                 {{ round($salesTrend, 1) }},
            salesTrendPositive:         {{ $salesTrend >= 0 ? 'true' : 'false' }},
            chartLabel:                 @json($chartLabel),
        },

        chartData: @json($chartData),

        get modalPartyLabel() {
            return {
                sales:     'Client',
                profit:    'Client',
                purchases: 'Fournisseur',
                expenses:  'Catégorie',
                recette:   'Vente liée',
            }[this.modalCard] ?? '—';
        },

        init() {
            this.$nextTick(() => this.buildChart());
        },

        openModal(card, title) {
            this.modalCard  = card;
            this.modalTitle = title;
            this.modalOpen  = true;
            this.modalCurrentPage = 1;
            this.fetchModalPage();
        },

        closeModal() {
            this.modalOpen = false;
        },

        modalGoTo(page) {
            if (page < 1 || page > this.modalLastPage) return;
            this.modalCurrentPage = page;
            this.fetchModalPage();
        },

        async fetchModalPage() {
            this.modalLoading = true;
            try {
                const params = new URLSearchParams({
                    card: this.modalCard,
                    period: this.period,
                    date_from: this.dateFrom,
                    date_to: this.dateTo,
                    page: this.modalCurrentPage,
                });
                const res  = await fetch(`{{ route('dashboard.card-detail') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                });
                const data = await res.json();

                this.modalRows        = data.data;
                this.modalTotal       = data.total;
                this.modalFrom        = data.from;
                this.modalTo          = data.to;
                this.modalCurrentPage = data.current_page;
                this.modalLastPage    = data.last_page;
            } catch (e) {
                console.error('Modal detail error:', e);
            } finally {
                this.modalLoading = false;
            }
        },

        setToday() {
            if (this.period === 'today' || this.loading) return;
            const today = new Date().toISOString().slice(0, 10);
            this.dateFrom = today;
            this.dateTo   = today;
            this.fetchStats('today');
        },

        setCustom() {
            if (!this.dateFrom || !this.dateTo || this.loading) return;
            if (this.dateFrom > this.dateTo) return;
            this.fetchStats('custom');
        },

        async fetchStats(period) {
            this.period  = period;
            this.loading = true;

            try {
                const params = new URLSearchParams({ period });
                if (period === 'custom') {
                    params.set('date_from', this.dateFrom);
                    params.set('date_to', this.dateTo);
                }
                const res  = await fetch(`{{ route('dashboard.stats') }}?${params.toString()}`, {
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
                        },
                        {
                            label: 'Recette',
                            data: this.chartData.recetteData,
                            backgroundColor: 'rgba(6,182,212,0.75)',
                            borderRadius: 6,
                            borderSkipped: false,
                        },
                        {
                            label: 'Bénéfice',
                            data: this.chartData.profitData,
                            backgroundColor: 'rgba(124,58,237,0.75)',
                            borderRadius: 6,
                            borderSkipped: false,
                        },
                        {
                            label: 'Dépenses',
                            data: this.chartData.expensesData,
                            backgroundColor: 'rgba(196,35,26,0.7)',
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
