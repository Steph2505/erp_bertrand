@extends('layouts.app')
@section('title', 'Rapport fournisseurs')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Fournisseurs</span>@endsection

@section('content')
<div x-data="{...listPage('{{ route('reports.api.suppliers') }}'), view: 'list', chartInstance: null, supplierOptions: @js($supplierOptions),
        renderChart() {
            const items = this.extra.chart_items ?? [];
            const labels = items.map(i => i.name);
            const totals = items.map(i => i.total_purchases);
            const debts  = items.map(i => i.debt);
            if (this.chartInstance) {
                this.chartInstance.data.labels = labels;
                this.chartInstance.data.datasets[0].data = totals;
                this.chartInstance.data.datasets[1].data = debts;
                this.chartInstance.update();
                return;
            }
            const ctx = document.getElementById('suppliersChart');
            if (!ctx) return;
            this.chartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        { label: 'Total achats', data: totals, backgroundColor: 'rgba(23,73,179,.7)', borderRadius: 4 },
                        { label: 'Dette', data: debts, backgroundColor: 'rgba(196,35,26,.7)', borderRadius: 4 },
                    ],
                },
                options: { responsive: true, scales: { y: { beginAtZero: true } } },
            });
        }
     }"
     x-init="filters = {date_from: '{{ $from }}', date_to: '{{ $to }}', supplier: ''};
             $watch('extra', () => { if (view === 'chart') renderChart(); });
             fetch()">
<div class="page-header">
    <div class="page-header__title"><h2>Fournisseurs</h2><p>Volume d'achat et dette par fournisseur</p></div>
    <div class="page-header__actions">
        <div class="filter-tabs">
            <a href="{{ route('reports.customers') }}" class="filter-tabs__btn">Clients</a>
            <a href="{{ route('reports.suppliers') }}" class="filter-tabs__btn filter-tabs__btn--active">Fournisseurs</a>
        </div>
    </div>
</div>

<div class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;overflow:visible;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0"><label>Du</label><input type="date" x-model="filters.date_from" @change="reset()" class="form-control"></div>
        <div class="form-group" style="margin:0"><label>Au</label><input type="date" x-model="filters.date_to" @change="reset()" class="form-control"></div>

        {{-- Fournisseur : select personnalisé (autocomplete) --}}
        <div class="form-group" style="margin:0;min-width:220px;" x-data="{ search: '', open: false }" @click.outside="open=false">
            <label>Fournisseur</label>
            <div class="autocomplete-wrap">
                <input type="text" x-model="search" @focus="open=true; search=''" @input="open=true"
                       :placeholder="filters.supplier || 'Tous les fournisseurs...'" class="form-control" autocomplete="off">
                <div x-show="open" x-transition class="autocomplete-dropdown">
                    <div @mousedown.prevent="filters.supplier=''; search=''; open=false; reset()"
                         class="autocomplete-dropdown__item" style="color:#64748B;">
                        Tous les fournisseurs
                    </div>
                    <template x-for="s in supplierOptions.filter(s => s.toLowerCase().includes(search.toLowerCase()))" :key="s">
                        <div @mousedown.prevent="filters.supplier=s; search=s; open=false; reset()"
                             class="autocomplete-dropdown__item"
                             :style="filters.supplier===s?'background:#eff6ff;color:#3b82f6;font-weight:600':''">
                            <span x-text="s"></span>
                        </div>
                    </template>
                    <template x-if="supplierOptions.filter(s => s.toLowerCase().includes(search.toLowerCase())).length === 0">
                        <div class="autocomplete-dropdown__empty">Aucun résultat</div>
                    </template>
                </div>
            </div>
        </div>

        <button type="button" @click="filters={date_from:'{{ $from }}',date_to:'{{ $to }}',supplier:''}; reset()" class="btn btn--ghost">Réinitialiser</button>
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

<div class="stat-grid" style="margin-bottom:16px;position:relative;">
    <div x-show="loading && rows.length > 0" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
        <svg style="width:28px;height:28px;color:#1749B3;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
    </div>
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Total achats</div><div class="stat-card__value" style="color:#1749B3;" x-text="formatMoney(extra.grand_total)"></div><div class="stat-card__trend stat-card__trend--flat">Sur la période</div></div></div>
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Dette totale</div><div class="stat-card__value" style="color:#C4231A;" x-text="formatMoney(extra.grand_debt)"></div><div class="stat-card__trend stat-card__trend--flat">Solde impayé, toutes périodes</div></div></div>
</div>

<div x-show="view === 'chart'" x-cloak class="table-wrapper" style="padding:20px;margin-bottom:16px;">
    <strong style="display:block;margin-bottom:16px;">Total achats et dette par fournisseur</strong>
    <canvas id="suppliersChart" height="90"></canvas>
</div>

<div x-show="view === 'list'">
<div class="table-wrapper">
    <div style="position:relative;">
        <div x-show="loading && rows.length > 0" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
            <svg style="width:28px;height:28px;color:#1749B3;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        </div>
        <div class="table-wrapper__header">
            <strong style="color:#1749B3;">Tous les fournisseurs</strong>
            <span style="font-size:13px;color:#64748B;" x-text="total + ' fournisseur(s)'">—</span>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>#</th><th>Fournisseur</th>
                <th style="text-align:right">Nb achats</th>
                <th style="text-align:right">Total</th>
                <th style="text-align:right">Dette</th>
            </tr></thead>
            <tbody>
                <template x-if="loading && rows.length === 0">
                    <tr><td colspan="5" style="text-align:center;padding:24px;color:#64748B;">Chargement...</td></tr>
                </template>
                <template x-if="!loading && rows.length === 0">
                    <tr><td colspan="5" style="text-align:center;padding:24px;color:#64748B;">Aucun achat sur la période</td></tr>
                </template>
                <template x-for="(s, i) in rows" :key="s.id">
                    <tr>
                        <td style="color:#64748B;font-weight:700;" x-text="'#' + (from + i)"></td>
                        <td>
                            <a :href="s.show_url" style="color:#1749B3;font-weight:500;" x-text="s.name"></a>
                        </td>
                        <td style="text-align:right" x-text="s.nb_purchases"></td>
                        <td style="text-align:right;font-weight:600;color:#1749B3;" x-text="formatMoney(s.total_purchases)"></td>
                        <td style="text-align:right;">
                            <template x-if="s.debt > 0">
                                <strong style="color:#C4231A;" x-text="formatMoney(s.debt)"></strong>
                            </template>
                            <template x-if="s.debt <= 0">
                                <span style="color:#CBD5E1;">—</span>
                            </template>
                        </td>
                    </tr>
                </template>
            </tbody>
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
</div>
@include('components.list-page-script')
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>function formatMoney(v) { return new Intl.NumberFormat('fr-FR').format(Math.round(v || 0)) + ' ' + window.CURRENCY; }</script>
@endpush
@endsection
