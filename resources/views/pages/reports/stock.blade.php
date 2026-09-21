@extends('layouts.app')
@section('title', 'Rapport de stock')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Stock</span>@endsection

@section('content')
<div x-data="{...listPage('{{ route('reports.api.stock') }}')}" x-init="filters = {search: '', category_id: '', status: ''}; fetch()">
<div class="page-header">
    <div class="page-header__title"><h2>Rapport de stock</h2><p>État des stocks en temps réel</p></div>
    <div class="page-header__actions">
        <div class="filter-tabs">
            <a href="{{ route('reports.stock') }}" class="filter-tabs__btn {{ request()->routeIs('reports.stock') ? 'filter-tabs__btn--active' : '' }}">Stock</a>
            <a href="{{ route('reports.stock-adjustment') }}" class="filter-tabs__btn {{ request()->routeIs('reports.stock-adjustment') ? 'filter-tabs__btn--active' : '' }}">Ajustements</a>
        </div>
    </div>
</div>

<div class="stat-grid" style="margin-bottom:20px;">
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Articles total</div><div class="stat-card__value">{{ $totalItems }}</div><div class="stat-card__trend stat-card__trend--flat">Références actives</div></div><div class="stat-card__icon stat-card__icon--blue"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg></div></div>
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Valeur du stock</div><div class="stat-card__value">{{ \App\Helpers\FormatHelper::money($totalValue) }}</div><div class="stat-card__trend stat-card__trend--flat">Au coût d'achat</div></div><div class="stat-card__icon stat-card__icon--green"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/></svg></div></div>
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Stock faible</div><div class="stat-card__value" style="color:#B45309;">{{ $lowCount }}</div><div class="stat-card__trend stat-card__trend--down">Sous le minimum</div></div><div class="stat-card__icon stat-card__icon--yellow"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg></div></div>
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Rupture</div><div class="stat-card__value" style="color:#C4231A;">{{ $outCount }}</div><div class="stat-card__trend stat-card__trend--down">Stock = 0</div></div><div class="stat-card__icon stat-card__icon--red"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg></div></div>
</div>

<div class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0"><label>Recherche</label><input type="text" x-model="filters.search" @input.debounce.400ms="reset()" class="form-control" placeholder="Nom article…"></div>
        <div class="form-group" style="margin:0"><label>Catégorie</label>
            <select x-model="filters.category_id" @change="reset()" class="form-select">
                <option value="">Toutes</option>
                @foreach($categories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach
            </select>
        </div>
        <div class="form-group" style="margin:0"><label>Statut</label>
            <select x-model="filters.status" @change="reset()" class="form-select">
                <option value="">Tous</option>
                <option value="ok">OK</option>
                <option value="low">Stock faible</option>
                <option value="out">Rupture</option>
            </select>
        </div>
        <button type="button" @click="clearFilters()" class="btn btn--ghost">Réinitialiser</button>
    </div>
</div>

<div class="table-wrapper">
    <div class="table-wrapper__header"><strong>Articles</strong><span style="font-size:13px;color:#64748B;" x-text="total + ' résultat(s)'">—</span></div>
    <div style="position:relative;">
        <div x-show="loading && rows.length > 0" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
            <svg style="width:28px;height:28px;color:#1749B3;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>Article</th><th>Catégorie</th><th>Unité</th>
                <th style="text-align:right">Stock actuel</th>
                <th style="text-align:right">Stock min.</th>
                <th style="text-align:right">Valeur (coût)</th>
                <th>Statut</th>
            </tr></thead>
            <tbody>
                <template x-if="loading && rows.length === 0">
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:#64748B;">Chargement...</td></tr>
                </template>
                <template x-if="!loading && rows.length === 0">
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:#64748B;">Aucun article</td></tr>
                </template>
                <template x-for="(p, idx) in rows" :key="idx">
                    <tr>
                        <td><strong x-text="p.name"></strong></td>
                        <td x-text="p.category_name ?? '—'"></td>
                        <td x-text="p.unit_abbr ?? '—'"></td>
                        <td :style="'text-align:right;font-weight:600;color:' + (p.status==='out'?'#C4231A':(p.status==='low'?'#B45309':'#12864B'))" x-text="p.stock_quantity"></td>
                        <td style="text-align:right;color:#64748B;" x-text="p.min_stock"></td>
                        <td style="text-align:right" x-text="p.value"></td>
                        <td>
                            <span class="badge" :class="p.status==='out'?'badge--red':(p.status==='low'?'badge--yellow':'badge--green')" x-text="p.status==='out'?'Rupture':(p.status==='low'?'Faible':'OK')"></span>
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
@include('components.list-page-script')
@endsection
