@extends('layouts.app')
@section('title', 'Ventes par article')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Ventes par article</span>@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title"><h2>Ventes par article</h2><p>CA par article sur la période</p></div>
    <div class="page-header__actions">
        <div class="filter-tabs">
            <a href="{{ route('reports.product-sale') }}" class="filter-tabs__btn filter-tabs__btn--active">Vente article</a>
            <a href="{{ route('reports.product-purchase') }}" class="filter-tabs__btn">Achat article</a>
        </div>
    </div>
</div>

<div x-data="{...listPage('{{ route('reports.api.product-sale') }}'), ...productSaleExtra()}"
     x-init="filters = {date_from: '{{ $from }}', date_to: '{{ $to }}', sort: '{{ $sort }}', item: ''}; fetch()">

<div class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;overflow:visible;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0">
            <label>Du</label>
            <input type="date" x-model="filters.date_from" @change="reset()" class="form-control">
        </div>
        <div class="form-group" style="margin:0">
            <label>Au</label>
            <input type="date" x-model="filters.date_to" @change="reset()" class="form-control">
        </div>

        {{-- Article : select personnalisé (autocomplete) --}}
        <div class="form-group" style="margin:0;min-width:220px;" x-data="{ search: '', open: false }" @click.outside="open=false">
            <label>Article</label>
            <div class="autocomplete-wrap">
                <input type="text" x-model="search" @focus="open=true; search=''" @input="open=true"
                       :placeholder="filters.item || 'Tous les articles...'" class="form-control" autocomplete="off">
                <div x-show="open" x-transition class="autocomplete-dropdown">
                    <div @mousedown.prevent="filters.item=''; search=''; open=false; reset()"
                         class="autocomplete-dropdown__item" style="color:#64748B;">
                        Tous les articles
                    </div>
                    <template x-for="a in articles.filter(a => a.toLowerCase().includes(search.toLowerCase()))" :key="a">
                        <div @mousedown.prevent="filters.item=a; search=a; open=false; reset()"
                             class="autocomplete-dropdown__item"
                             :style="filters.item===a?'background:#eff6ff;color:#3b82f6;font-weight:600':''">
                            <span x-text="a"></span>
                        </div>
                    </template>
                    <template x-if="articles.filter(a => a.toLowerCase().includes(search.toLowerCase())).length === 0">
                        <div class="autocomplete-dropdown__empty">Aucun résultat</div>
                    </template>
                </div>
            </div>
        </div>

        <div class="form-group" style="margin:0">
            <label>Trier par</label>
            <select x-model="filters.sort" @change="reset()" class="form-select">
                <option value="revenue">Chiffre d'affaires</option>
                <option value="profit">Bénéfice</option>
                <option value="qty">Quantité vendue</option>
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

<div x-show="view === 'chart'" x-cloak class="table-wrapper" style="padding:20px;margin-bottom:16px;">
    <strong style="display:block;margin-bottom:16px;">Chiffre d'affaires et bénéfice par article (top 15)</strong>
    <canvas id="productSaleChart" height="90"></canvas>
</div>

<div x-show="view === 'list'">
<div class="table-wrapper">
    <div style="position:relative;">
        <div x-show="loading && rows.length > 0" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
            <svg style="width:28px;height:28px;color:#1749B3;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        </div>
        <div class="table-wrapper__header">
            <strong>Ventes par article</strong>
            <span style="font-size:13px;color:#64748B;" x-text="total + ' article(s) — CA total : ' + formatMoney(extra.total_revenue || 0)"></span>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>#</th><th>Article</th>
                <th style="text-align:right">Qté</th>
                <th style="text-align:right">Nb factures</th>
                <th style="text-align:right">CA</th>
                <th style="text-align:right">Bénéfice</th>
                <th style="text-align:right">Part</th>
            </tr></thead>
            <tbody>
                <template x-if="loading && rows.length === 0">
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:#64748B;">Chargement...</td></tr>
                </template>
                <template x-if="!loading && rows.length === 0">
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:#64748B;">Aucune vente sur la période</td></tr>
                </template>
                <template x-for="(item, i) in rows" :key="item.item_name + item.item_type">
                    <tr>
                        <td style="color:#64748B;font-weight:600;" x-text="'#' + (from + i)"></td>
                        <td><strong x-text="item.item_name"></strong></td>
                        <td style="text-align:right" x-text="formatNumber(item.total_qty)"></td>
                        <td style="text-align:right;color:#64748B;" x-text="item.nb_sales"></td>
                        <td style="text-align:right;font-weight:600;color:#12864B;" x-text="formatMoney(item.total_revenue)"></td>
                        <td :style="'text-align:right;font-weight:600;color:' + (item.total_profit >= 0 ? '#12864B' : '#C4231A')" x-text="formatMoney(item.total_profit)"></td>
                        <td style="text-align:right;">
                            <div style="display:flex;align-items:center;gap:6px;justify-content:flex-end;">
                                <div style="width:50px;height:6px;background:#E2E8F0;border-radius:3px;overflow:hidden;">
                                    <div :style="'height:100%;background:#1749B3;border-radius:3px;width:' + share(item) + '%'"></div>
                                </div>
                                <span style="font-size:12px;color:#64748B;" x-text="share(item).toFixed(1) + '%'"></span>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
            <tfoot x-show="total > 0">
                <tr style="border-top:2px solid #E2E8F0;background:#F8FAFC;font-weight:700;">
                    <td colspan="2">TOTAL</td>
                    <td style="text-align:right" x-text="formatNumber(extra.total_qty || 0)"></td>
                    <td></td>
                    <td style="text-align:right;color:#12864B;" x-text="formatMoney(extra.total_revenue || 0)"></td>
                    <td style="text-align:right;color:#12864B;" x-text="formatMoney(extra.total_profit || 0)"></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="table-wrapper__footer">
        <span x-text="from + '–' + to + ' sur ' + total"></span>
        <div style="display:flex;gap:4px;" x-show="lastPage > 1">
            <button @click="goTo(currentPage-1)" :disabled="currentPage<=1||loading" class="btn btn--ghost btn--sm btn--icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg></button>
            <template x-for="p in pages" :key="p"><button @click="p!=='…'&&goTo(p)" class="btn btn--sm" :class="p===currentPage?'btn--primary':'btn--ghost'" :disabled="p==='…'||loading" x-text="p" style="min-width:34px;justify-content:center;"></button></template>
            <button @click="goTo(currentPage+1)" :disabled="currentPage>=lastPage||loading" class="btn btn--ghost btn--sm btn--icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg></button>
        </div>
    </div>
</div>
</div>

</div>{{-- /x-data --}}
@include('components.list-page-script')
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
function productSaleExtra() {
    return {
        view: 'list',
        articles: @json($articles),
        chartInstance: null,

        formatMoney(v) { return new Intl.NumberFormat('fr-FR').format(Math.round(v || 0)) + ' ' + window.CURRENCY; },
        formatNumber(v) { return new Intl.NumberFormat('fr-FR').format(v || 0); },

        share(item) {
            const total = this.extra.total_revenue || 0;
            return total > 0 ? (item.total_revenue / total * 100) : 0;
        },

        resetFilters() {
            this.filters = { date_from: '{{ $from }}', date_to: '{{ $to }}', sort: 'revenue', item: '' };
            this.reset();
        },

        renderChart() {
            const items = this.extra.chart_items || [];
            const labels      = items.map(i => i.item_name);
            const revenueData = items.map(i => i.total_revenue);
            const profitData  = items.map(i => i.total_profit);

            if (this.chartInstance) {
                this.chartInstance.data.labels = labels;
                this.chartInstance.data.datasets[0].data = revenueData;
                this.chartInstance.data.datasets[1].data = profitData;
                this.chartInstance.update();
                return;
            }
            const ctx = document.getElementById('productSaleChart');
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
