@extends('layouts.app')
@section('title', 'Rapport de stock')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Stock</span>@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title"><h2>Rapport de stock</h2><p>État des stocks en temps réel</p></div>
    <div class="page-header__actions">
        <div class="filter-tabs">
            <a href="{{ route('reports.stock') }}" class="filter-tabs__btn {{ request()->routeIs('reports.stock') ? 'filter-tabs__btn--active' : '' }}">Stock</a>
            <a href="{{ route('reports.expiry') }}" class="filter-tabs__btn {{ request()->routeIs('reports.expiry') ? 'filter-tabs__btn--active' : '' }}">Péremption</a>
            <a href="{{ route('reports.stock-adjustment') }}" class="filter-tabs__btn {{ request()->routeIs('reports.stock-adjustment') ? 'filter-tabs__btn--active' : '' }}">Ajustements</a>
        </div>
    </div>
</div>

<div class="stat-grid" style="margin-bottom:20px;">
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Produits total</div><div class="stat-card__value">{{ $totalItems }}</div><div class="stat-card__trend stat-card__trend--flat">Références actives</div></div><div class="stat-card__icon stat-card__icon--blue"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg></div></div>
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Valeur du stock</div><div class="stat-card__value">{{ \App\Helpers\FormatHelper::money($totalValue) }}</div><div class="stat-card__trend stat-card__trend--flat">Au coût d'achat</div></div><div class="stat-card__icon stat-card__icon--green"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/></svg></div></div>
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Stock faible</div><div class="stat-card__value" style="color:#B45309;">{{ $lowCount }}</div><div class="stat-card__trend stat-card__trend--down">Sous le minimum</div></div><div class="stat-card__icon stat-card__icon--yellow"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg></div></div>
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Rupture</div><div class="stat-card__value" style="color:#C4231A;">{{ $outCount }}</div><div class="stat-card__trend stat-card__trend--down">Stock = 0</div></div><div class="stat-card__icon stat-card__icon--red"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg></div></div>
</div>

<form method="GET" class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0"><label>Recherche</label><input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Nom produit…"></div>
        <div class="form-group" style="margin:0"><label>Catégorie</label>
            <select name="category_id" class="form-select">
                <option value="">Toutes</option>
                @foreach($categories as $cat)<option value="{{ $cat->id }}" {{ $categoryId==$cat->id?'selected':''}}>{{ $cat->name }}</option>@endforeach
            </select>
        </div>
        <div class="form-group" style="margin:0"><label>Statut</label>
            <select name="status" class="form-select">
                <option value="">Tous</option>
                <option value="ok" {{ $status==='ok'?'selected':''}}>OK</option>
                <option value="low" {{ $status==='low'?'selected':''}}>Stock faible</option>
                <option value="out" {{ $status==='out'?'selected':''}}>Rupture</option>
            </select>
        </div>
        <button class="btn btn--primary">Filtrer</button>
        <a href="{{ route('reports.stock') }}" class="btn btn--ghost">Réinitialiser</a>
    </div>
</form>

<div class="table-wrapper">
    <div class="table-wrapper__header"><strong>Produits</strong><span style="font-size:13px;color:#64748B;">{{ $products->total() }} résultat(s)</span></div>
    <table class="data-table">
        <thead><tr>
            <th>Produit</th><th>Catégorie</th><th>Unité</th>
            <th style="text-align:right">Stock actuel</th>
            <th style="text-align:right">Stock min.</th>
            <th style="text-align:right">Valeur (coût)</th>
            <th>Statut</th>
        </tr></thead>
        <tbody>
            @forelse($products as $p)
            @php
                $isOut = $p->stock_quantity <= 0;
                $isLow = !$isOut && $p->stock_quantity <= $p->min_stock_quantity;
            @endphp
            <tr>
                <td><strong>{{ $p->display_name }}</strong></td>
                <td>{{ $p->category?->name ?? '—' }}</td>
                <td>{{ $p->unit?->abbreviation ?? '—' }}</td>
                <td style="text-align:right;font-weight:600;color:{{ $isOut?'#C4231A':($isLow?'#B45309':'#12864B') }}">{{ \App\Helpers\FormatHelper::number($p->stock_quantity) }}</td>
                <td style="text-align:right;color:#64748B;">{{ \App\Helpers\FormatHelper::number($p->min_stock_quantity) }}</td>
                <td style="text-align:right">{{ \App\Helpers\FormatHelper::money($p->stock_quantity * $p->buying_price) }}</td>
                <td>
                    @if($isOut) <span class="badge badge--red">Rupture</span>
                    @elseif($isLow) <span class="badge badge--yellow">Faible</span>
                    @else <span class="badge badge--green">OK</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;padding:40px;color:#64748B;">Aucun produit</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="table-wrapper__footer">
        <span>{{ $products->firstItem()??0 }}–{{ $products->lastItem()??0 }} sur {{ $products->total() }}</span>
        {{ $products->withQueryString()->links() }}
    </div>
</div>
@endsection
