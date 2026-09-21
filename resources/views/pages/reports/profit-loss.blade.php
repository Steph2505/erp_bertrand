@extends('layouts.app')
@section('title', 'Profit / Perte')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Profit / Perte</span>@endsection

@php
    $ranges = [
        'day'   => [now()->toDateString(),               now()->toDateString()],
        'week'  => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
        'month' => [now()->startOfMonth()->toDateString(),now()->endOfMonth()->toDateString()],
        'year'  => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
    ];
    $activePeriod = collect($ranges)->search(fn($r) => $r[0] === $from && $r[1] === $to) ?: null;
@endphp

@section('content')
<div x-data="profitLossPage('{{ route('reports.api.profit-loss') }}', {date_from: '{{ $from }}', date_to: '{{ $to }}'})" x-init="fetch()">
<div class="page-header">
    <div class="page-header__title">
        <h2>Profit / Perte</h2>
        <p x-text="'Du ' + formatDate(filters.date_from) + ' au ' + formatDate(filters.date_to)">—</p>
    </div>
    <div class="page-header__actions">
        <div class="filter-tabs">
            @foreach(['day' => 'Jour', 'week' => 'Semaine', 'month' => 'Mois', 'year' => 'Année'] as $key => $label)
                <button type="button" @click="filters = {date_from: '{{ $ranges[$key][0] }}', date_to: '{{ $ranges[$key][1] }}'}; fetch()"
                   class="filter-tabs__btn" :class="filters.date_from === '{{ $ranges[$key][0] }}' && filters.date_to === '{{ $ranges[$key][1] }}' ? 'filter-tabs__btn--active' : ''">{{ $label }}</button>
            @endforeach
        </div>
    </div>
</div>

<div class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0"><label>Du</label><input type="date" x-model="filters.date_from" @change="fetch()" class="form-control"></div>
        <div class="form-group" style="margin:0"><label>Au</label><input type="date" x-model="filters.date_to" @change="fetch()" class="form-control"></div>
        <button type="button" @click="filters={date_from:'{{ $from }}',date_to:'{{ $to }}'}; fetch()" class="btn btn--ghost">Réinitialiser</button>
    </div>
</div>

{{-- KPI --}}
<div class="stat-grid" style="margin-bottom:24px;position:relative;">
    <div x-show="loading" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
        <svg style="width:28px;height:28px;color:#1749B3;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
    </div>
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Chiffre d'affaires</div>
            <div class="stat-card__value" style="color:#12864B;" x-text="formatMoney(data.revenue)"></div>
            <div class="stat-card__trend stat-card__trend--flat">Ventes confirmées</div>
        </div>
        <div class="stat-card__icon stat-card__icon--green">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Coût des articles vendus</div>
            <div class="stat-card__value" style="color:#1749B3;" x-text="formatMoney(data.cogs)"></div>
            <div class="stat-card__trend stat-card__trend--flat">Prix d'achat des articles vendus (POS + Vente)</div>
        </div>
        <div class="stat-card__icon stat-card__icon--blue">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Bénéfice brut</div>
            <div class="stat-card__value" :style="'color:' + ((data.gross_profit ?? 0) >= 0 ? '#12864B' : '#C4231A')" x-text="formatMoney(data.gross_profit)"></div>
            <div class="stat-card__trend stat-card__trend--flat">CA – Coût des articles vendus</div>
        </div>
        <div class="stat-card__icon" :class="(data.gross_profit ?? 0) >= 0 ? 'stat-card__icon--green' : 'stat-card__icon--red'">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/></svg>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Résultat net</div>
            <div class="stat-card__value" :style="'color:' + ((data.net_profit ?? 0) >= 0 ? '#12864B' : '#C4231A')" x-text="formatMoney(data.net_profit)"></div>
            <div class="stat-card__trend" :class="(data.net_profit ?? 0) >= 0 ? 'stat-card__trend--up' : 'stat-card__trend--down'" x-text="'Marge – Dépenses (' + formatMoney(data.expenses) + ')'"></div>
        </div>
        <div class="stat-card__icon" :class="(data.net_profit ?? 0) >= 0 ? 'stat-card__icon--green' : 'stat-card__icon--red'">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" :d="(data.net_profit ?? 0) >= 0 ? 'M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941' : 'M2.25 6 9 12.75l4.286-4.286a11.948 11.948 0 0 1 4.306 6.43l.776 2.898m0 0 3.182-5.511m-3.182 5.51-5.511-3.181'"/></svg>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">

    {{-- Graphique évolution --}}
    <div class="table-wrapper" style="padding:20px;">
        <strong style="display:block;margin-bottom:16px;">Ventes et bénéfice par articles vendus (POS + Vente) — évolution</strong>
        <canvas id="plChart" height="100"></canvas>
    </div>

    {{-- Dépenses par catégorie --}}
    <div class="table-wrapper">
        <div class="table-wrapper__header"><strong>Dépenses par catégorie</strong></div>
        <table class="data-table">
            <thead><tr><th>Catégorie</th><th style="text-align:right">Montant</th></tr></thead>
            <tbody>
                <template x-if="(data.expenses_by_category ?? []).length === 0">
                    <tr><td colspan="2" style="text-align:center;padding:24px;color:#64748B;">Aucune dépense</td></tr>
                </template>
                <template x-for="cat in (data.expenses_by_category ?? [])" :key="cat.name">
                    <tr>
                        <td x-text="cat.name"></td>
                        <td style="text-align:right;font-weight:600;color:#C4231A;" x-text="formatMoney(cat.amount)"></td>
                    </tr>
                </template>
                <tr x-show="(data.expenses ?? 0) > 0" style="border-top:2px solid #E2E8F0;font-weight:700;">
                    <td>TOTAL</td>
                    <td style="text-align:right;color:#C4231A;" x-text="formatMoney(data.expenses)"></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- Tableau mensuel récapitulatif --}}
<div class="table-wrapper" style="margin-top:20px;">
    <div class="table-wrapper__header"><strong>Récapitulatif mensuel</strong></div>
    <table class="data-table">
        <thead><tr>
            <th>Mois</th>
            <th style="text-align:right;color:#12864B;">CA</th>
            <th style="text-align:right;color:#1749B3;">Coût articles vendus</th>
            <th style="text-align:right;color:#C99A05;">Bénéfice</th>
            <th style="text-align:right;color:#B45309;">Dépenses</th>
            <th style="text-align:right">Résultat net</th>
        </tr></thead>
        <tbody>
            <template x-for="m in (data.monthly ?? [])" :key="m.label">
                <tr>
                    <td><strong x-text="m.label"></strong></td>
                    <td style="text-align:right" x-text="formatMoney(m.revenue)"></td>
                    <td style="text-align:right" x-text="formatMoney(m.cogs)"></td>
                    <td :style="'text-align:right;font-weight:600;color:' + (m.profit >= 0 ? '#12864B' : '#C4231A')" x-text="formatMoney(m.profit)"></td>
                    <td style="text-align:right" x-text="formatMoney(m.expenses)"></td>
                    <td :style="'text-align:right;font-weight:600;color:' + ((m.profit - m.expenses) >= 0 ? '#12864B' : '#C4231A')">
                        <span x-text="((m.profit - m.expenses) >= 0 ? '+' : '') + formatMoney(m.profit - m.expenses)"></span>
                    </td>
                </tr>
            </template>
        </tbody>
    </table>
</div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
function profitLossPage(apiUrl, initialFilters) {
    return {
        apiUrl,
        filters: { ...initialFilters },
        data: {},
        loading: true,
        chartInstance: null,

        async fetch() {
            this.loading = true;
            const params = new URLSearchParams();
            Object.entries(this.filters).forEach(([k, v]) => { if (v !== '' && v !== null && v !== undefined) params.set(k, v); });
            try {
                const res = await fetch(this.apiUrl + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                });
                this.data = await res.json();
                this.$nextTick(() => this.renderChart());
            } catch (e) { console.error(e); }
            finally { this.loading = false; }
        },

        formatMoney(v) { return new Intl.NumberFormat('fr-FR').format(Math.round(v || 0)) + ' ' + window.CURRENCY; },
        formatDate(d) { if (!d) return '—'; const [y,m,day] = d.split('-'); return day + '/' + m + '/' + y; },

        renderChart() {
            const monthly = this.data.monthly ?? [];
            const labels = monthly.map(m => m.label);
            const revenue = monthly.map(m => m.revenue);
            const cogs = monthly.map(m => m.cogs);
            const expenses = monthly.map(m => m.expenses);
            const profit = monthly.map(m => m.profit);

            if (this.chartInstance) {
                this.chartInstance.data.labels = labels;
                this.chartInstance.data.datasets[0].data = revenue;
                this.chartInstance.data.datasets[1].data = cogs;
                this.chartInstance.data.datasets[2].data = expenses;
                this.chartInstance.data.datasets[3].data = profit;
                this.chartInstance.update();
                return;
            }
            const ctx = document.getElementById('plChart');
            if (!ctx) return;
            this.chartInstance = new Chart(ctx, {
                data: {
                    labels,
                    datasets: [
                        { type: 'bar', label: 'Ventes (CA)', data: revenue, backgroundColor: 'rgba(34,197,94,.7)', borderRadius: 4 },
                        { type: 'bar', label: 'Coût articles vendus', data: cogs, backgroundColor: 'rgba(59,130,246,.65)', borderRadius: 4 },
                        { type: 'bar', label: 'Dépenses', data: expenses, backgroundColor: 'rgba(245,158,11,.65)', borderRadius: 4 },
                        { type: 'line', label: 'Bénéfice', data: profit, borderColor: '#C99A05', backgroundColor: 'rgba(201,154,5,.1)', borderWidth: 2.5, pointRadius: 4, pointBackgroundColor: '#C99A05', tension: .3, fill: false },
                    ]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'top' }, tooltip: { callbacks: { label: c => c.dataset.label+' : '+new Intl.NumberFormat('fr-FR').format(c.parsed.y)+' ' + window.CURRENCY } } },
                    scales: { y: { ticks: { callback: v => new Intl.NumberFormat('fr-FR',{notation:'compact'}).format(v)+' ' + window.CURRENCY } } }
                }
            });
        },
    };
}
</script>
@endpush
