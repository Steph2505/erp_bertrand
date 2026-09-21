@extends('layouts.app')
@section('title', 'Bénéfice par catégorie')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Bénéfice par catégorie</span>@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title"><h2>Bénéfice par catégorie</h2><p>Chiffre d'affaires, coût et marge par catégorie d'article sur la période</p></div>
</div>

<div x-data="categoryProfitPage('{{ route('reports.api.category-profit') }}')"
     x-init="filters = {date_from: '{{ $from }}', date_to: '{{ $to }}', sort: '{{ $sort }}', category: ''}; fetch()">

<div class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;overflow:visible;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0">
            <label>Du</label>
            <input type="date" x-model="filters.date_from" @change="fetch()" class="form-control">
        </div>
        <div class="form-group" style="margin:0">
            <label>Au</label>
            <input type="date" x-model="filters.date_to" @change="fetch()" class="form-control">
        </div>

        {{-- Catégorie : select personnalisé (autocomplete) --}}
        <div class="form-group" style="margin:0;min-width:220px;" x-data="{ search: '', open: false }" @click.outside="open=false">
            <label>Catégorie</label>
            <div class="autocomplete-wrap">
                <input type="text" x-model="search" @focus="open=true; search=''" @input="open=true"
                       :placeholder="filters.category || 'Toutes les catégories...'" class="form-control" autocomplete="off">
                <div x-show="open" x-transition class="autocomplete-dropdown">
                    <div @mousedown.prevent="filters.category=''; search=''; open=false; fetch()"
                         class="autocomplete-dropdown__item" style="color:#64748B;">
                        Toutes les catégories
                    </div>
                    <template x-for="c in categoryOptions.filter(c => c.toLowerCase().includes(search.toLowerCase()))" :key="c">
                        <div @mousedown.prevent="filters.category=c; search=c; open=false; fetch()"
                             class="autocomplete-dropdown__item"
                             :style="filters.category===c?'background:#eff6ff;color:#3b82f6;font-weight:600':''">
                            <span x-text="c"></span>
                        </div>
                    </template>
                    <template x-if="categoryOptions.filter(c => c.toLowerCase().includes(search.toLowerCase())).length === 0">
                        <div class="autocomplete-dropdown__empty">Aucun résultat</div>
                    </template>
                </div>
            </div>
        </div>

        <div class="form-group" style="margin:0">
            <label>Trier par</label>
            <select x-model="filters.sort" @change="fetch()" class="form-select">
                <option value="profit">Bénéfice</option>
                <option value="revenue">Chiffre d'affaires</option>
                <option value="name">Nom (A-Z)</option>
            </select>
        </div>

        <button type="button" @click="resetFilters()" class="btn btn--ghost">Réinitialiser</button>

        <button type="button" class="btn btn--ghost btn--icon" title="Voir en diagramme"
                @click="view = 'chart'; $nextTick(() => renderChart())" x-show="view === 'list'">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
        </button>
        <button type="button" class="btn btn--ghost btn--icon" title="Voir la liste"
                @click="view = 'list'" x-show="view === 'chart'" x-cloak>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
        </button>
    </div>
</div>

<div class="stat-grid" style="margin-bottom:20px;">
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Chiffre d'affaires</div><div class="stat-card__value" x-text="formatMoney(totals.revenue)"></div><div class="stat-card__trend stat-card__trend--flat">Sur la période</div></div><div class="stat-card__icon stat-card__icon--blue"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/></svg></div></div>
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Coût des articles vendus</div><div class="stat-card__value" x-text="formatMoney(totals.cost)"></div><div class="stat-card__trend stat-card__trend--flat">Au coût d'achat</div></div><div class="stat-card__icon stat-card__icon--yellow"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg></div></div>
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Bénéfice brut</div><div class="stat-card__value" style="color:#12864B;" x-text="formatMoney(totals.profit)"></div><div class="stat-card__trend stat-card__trend--flat">CA – coût</div></div><div class="stat-card__icon stat-card__icon--green"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.28m5.94 2.28-2.28 5.941"/></svg></div></div>
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Marge moyenne</div><div class="stat-card__value" x-text="totals.margin + '%'"></div><div class="stat-card__trend stat-card__trend--flat">Bénéfice / CA</div></div><div class="stat-card__icon stat-card__icon--blue"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg></div></div>
</div>

<div x-show="view === 'chart'" x-cloak class="table-wrapper" style="padding:20px;margin-bottom:16px;">
    <strong style="display:block;margin-bottom:16px;">Chiffre d'affaires et bénéfice par catégorie</strong>
    <canvas id="categoryProfitChart" height="90"></canvas>
</div>

<div x-show="view === 'list'">
<div class="table-wrapper">
    <div style="position:relative;">
        <div x-show="loading" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
            <svg style="width:28px;height:28px;color:#1749B3;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        </div>
        <div class="table-wrapper__header">
            <strong>Bénéfice par catégorie</strong>
            <span style="font-size:13px;color:#64748B;" x-text="categories.length + ' catégorie(s)'"></span>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>#</th><th>Catégorie</th>
                <th style="text-align:right">Qté vendue</th>
                <th style="text-align:right">CA</th>
                <th style="text-align:right">Coût</th>
                <th style="text-align:right">Bénéfice</th>
                <th style="text-align:right">Marge</th>
            </tr></thead>
            <tbody>
                <template x-if="!loading && categories.length === 0">
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:#64748B;">Aucune vente sur la période</td></tr>
                </template>
                <template x-for="(cat, i) in categories" :key="cat.category">
                    <tr>
                        <td style="color:#64748B;font-weight:600;" x-text="'#' + (i+1)"></td>
                        <td><strong x-text="cat.category"></strong></td>
                        <td style="text-align:right" x-text="formatNumber(cat.qty)"></td>
                        <td style="text-align:right" x-text="formatMoney(cat.revenue)"></td>
                        <td style="text-align:right;color:#64748B;" x-text="formatMoney(cat.cost)"></td>
                        <td :style="'text-align:right;font-weight:600;color:' + (cat.profit >= 0 ? '#12864B' : '#C4231A')" x-text="formatMoney(cat.profit)"></td>
                        <td style="text-align:right;">
                            <span class="badge" :class="'badge--' + marginColor(cat.margin)" x-text="cat.margin + '%'"></span>
                        </td>
                    </tr>
                </template>
            </tbody>
            <tfoot x-show="categories.length > 0">
                <tr style="border-top:2px solid #E2E8F0;background:#F8FAFC;font-weight:700;">
                    <td colspan="2">TOTAL</td>
                    <td style="text-align:right" x-text="formatNumber(totals.qty)"></td>
                    <td style="text-align:right" x-text="formatMoney(totals.revenue)"></td>
                    <td style="text-align:right" x-text="formatMoney(totals.cost)"></td>
                    <td style="text-align:right;color:#12864B;" x-text="formatMoney(totals.profit)"></td>
                    <td style="text-align:right" x-text="totals.margin + '%'"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
</div>

</div>{{-- /x-data --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
function categoryProfitPage(apiUrl) {
    return {
        apiUrl,
        loading: true,
        view: 'list',
        filters: {},
        categories: [],
        totals: { revenue: 0, cost: 0, profit: 0, qty: 0, margin: 0 },
        categoryOptions: @json($categoryOptions),
        chartInstance: null,

        async fetch() {
            this.loading = true;
            const params = new URLSearchParams();
            Object.entries(this.filters).forEach(([k, v]) => { if (v !== '' && v !== null && v !== undefined) params.set(k, v); });
            try {
                const res = await fetch(this.apiUrl + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                });
                const data = await res.json();
                this.categories = data.categories;
                this.totals = {
                    revenue: data.total_revenue,
                    cost:    data.total_cost,
                    profit:  data.total_profit,
                    qty:     data.total_qty,
                    margin:  data.total_margin,
                };
                if (this.view === 'chart') this.renderChart();
            } catch (e) { console.error(e); }
            finally { this.loading = false; }
        },

        resetFilters() {
            this.filters = { date_from: '{{ $from }}', date_to: '{{ $to }}', sort: 'profit', category: '' };
            this.fetch();
        },

        formatMoney(v) { return new Intl.NumberFormat('fr-FR').format(Math.round(v || 0)) + ' ' + window.CURRENCY; },
        formatNumber(v) { return new Intl.NumberFormat('fr-FR').format(v || 0); },
        marginColor(margin) { return margin >= 30 ? 'green' : (margin >= 10 ? 'yellow' : 'red'); },

        renderChart() {
            const labels      = this.categories.map(c => c.category);
            const revenueData = this.categories.map(c => c.revenue);
            const profitData  = this.categories.map(c => c.profit);

            if (this.chartInstance) {
                this.chartInstance.data.labels = labels;
                this.chartInstance.data.datasets[0].data = revenueData;
                this.chartInstance.data.datasets[1].data = profitData;
                this.chartInstance.update();
                return;
            }
            const ctx = document.getElementById('categoryProfitChart');
            if (!ctx) return;
            this.chartInstance = new Chart(ctx, {
                data: {
                    labels,
                    datasets: [
                        { type: 'bar', label: 'CA',       data: revenueData, backgroundColor: 'rgba(34,197,94,.7)', borderRadius: 4 },
                        { type: 'bar', label: 'Bénéfice', data: profitData,  backgroundColor: 'rgba(23,73,179,.7)', borderRadius: 4 },
                    ],
                },
                options: { responsive: true, scales: { y: { beginAtZero: true } } },
            });
        },
    };
}
</script>
@endpush
@endsection
