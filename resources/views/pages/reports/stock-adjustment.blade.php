@extends('layouts.app')
@section('title', 'Ajustements de stock')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Ajustements</span>@endsection

@section('content')
<div x-data="{...listPage('{{ route('reports.api.stock-adjustment') }}')}" x-init="filters = {date_from: '{{ $from }}', date_to: '{{ $to }}'}; fetch()">
<div class="page-header">
    <div class="page-header__title"><h2>Ajustements de stock</h2><p>Mouvements manuels sur la période</p></div>
    <div class="page-header__actions">
        <div class="filter-tabs">
            <a href="{{ route('reports.stock') }}" class="filter-tabs__btn">Stock</a>
            <a href="{{ route('reports.stock-adjustment') }}" class="filter-tabs__btn filter-tabs__btn--active">Ajustements</a>
        </div>
    </div>
</div>

<div class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0"><label>Du</label><input type="date" x-model="filters.date_from" @change="reset()" class="form-control"></div>
        <div class="form-group" style="margin:0"><label>Au</label><input type="date" x-model="filters.date_to" @change="reset()" class="form-control"></div>
        <button type="button" @click="clearFilters()" class="btn btn--ghost">Réinitialiser</button>
    </div>
</div>

<div class="stat-grid" style="margin-bottom:20px;">
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Unités ajoutées</div><div class="stat-card__value" style="color:#12864B;"><span x-text="'+' + (extra.total_added ?? 0)"></span></div><div class="stat-card__trend stat-card__trend--up">Entrées manuelles</div></div><div class="stat-card__icon stat-card__icon--green"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg></div></div>
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Unités déduites</div><div class="stat-card__value" style="color:#C4231A;"><span x-text="'−' + (extra.total_deducted ?? 0)"></span></div><div class="stat-card__trend stat-card__trend--down">Sorties manuelles</div></div><div class="stat-card__icon stat-card__icon--red"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg></div></div>
</div>

<div class="table-wrapper">
    <div class="table-wrapper__header"><strong>Mouvements d'ajustement</strong><span style="font-size:13px;color:#64748B;" x-text="total + ' mouvement(s)'">—</span></div>
    <div style="position:relative;">
        <div x-show="loading && rows.length > 0" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
            <svg style="width:28px;height:28px;color:#1749B3;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        </div>
        <table class="data-table">
            <thead><tr><th>Date</th><th>Article</th><th>Entrepôt</th><th>Type</th><th style="text-align:right">Quantité</th><th>Note</th><th>Par</th></tr></thead>
            <tbody>
                <template x-if="loading && rows.length === 0">
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:#64748B;">Chargement...</td></tr>
                </template>
                <template x-if="!loading && rows.length === 0">
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:#64748B;">Aucun ajustement sur la période</td></tr>
                </template>
                <template x-for="(m, idx) in rows" :key="idx">
                    <tr>
                        <td style="font-size:12px;color:#64748B;" x-text="m.date"></td>
                        <td><strong x-text="m.product_name ?? '—'"></strong></td>
                        <td x-text="m.warehouse ?? '—'"></td>
                        <td><span class="badge" :class="m.type==='addition'?'badge--green':(m.type==='subtraction'?'badge--red':'badge--gray')" x-text="m.type.charAt(0).toUpperCase() + m.type.slice(1)"></span></td>
                        <td :style="'text-align:right;font-weight:600;color:' + (m.quantity>0?'#12864B':'#C4231A')">
                            <span x-text="(m.quantity > 0 ? '+' : '') + m.quantity"></span>
                        </td>
                        <td style="font-size:12px;color:#64748B;" x-text="m.note ?? '—'"></td>
                        <td style="font-size:12px;" x-text="m.created_by ?? '—'"></td>
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
@include('components.list-page-script')
@endsection
