@extends('layouts.app')
@section('title', 'Rapport des articles')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Articles</span>@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title"><h2>Rapport des articles</h2><p>Tous les articles vendus sur la période</p></div>
    <div class="page-header__actions">
        <div class="filter-tabs">
            <a href="{{ route('reports.trending') }}" class="filter-tabs__btn">Tendances</a>
            <a href="{{ route('reports.items') }}" class="filter-tabs__btn filter-tabs__btn--active">Articles</a>
            <a href="{{ route('reports.product-sale') }}" class="filter-tabs__btn">Vente produit</a>
            <a href="{{ route('reports.product-purchase') }}" class="filter-tabs__btn">Achat produit</a>
        </div>
    </div>
</div>

<form method="GET" class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0"><label>Du</label><input type="date" name="date_from" value="{{ $from }}" class="form-control"></div>
        <div class="form-group" style="margin:0"><label>Au</label><input type="date" name="date_to" value="{{ $to }}" class="form-control"></div>
        <button class="btn btn--primary">Filtrer</button>
        <a href="{{ route('reports.items') }}" class="btn btn--ghost">Réinitialiser</a>
    </div>
</form>

<div class="table-wrapper">
    <div class="table-wrapper__header">
        <strong>Articles vendus</strong>
        <span style="font-size:13px;color:#64748B;">
            {{ $items->count() }} article(s) — CA total : <strong>{{ \App\Helpers\FormatHelper::money($items->sum('total_revenue')) }}</strong>
        </span>
    </div>
    <table class="data-table">
        <thead><tr>
            <th>Article</th><th>Type</th>
            <th style="text-align:right">Qté vendue</th>
            <th style="text-align:right">Prix moyen</th>
            <th style="text-align:right">Nb ventes</th>
            <th style="text-align:right">CA</th>
        </tr></thead>
        <tbody>
            @forelse($items as $item)
            <tr>
                <td><strong>{{ $item->item_name }}</strong></td>
                <td><span class="badge badge--{{ $item->item_type==='pack'?'pack':'gray' }}">{{ $item->item_type==='pack'?'Pack':'Produit' }}</span></td>
                <td style="text-align:right">{{ \App\Helpers\FormatHelper::number($item->total_qty) }}</td>
                <td style="text-align:right;color:#64748B;">{{ \App\Helpers\FormatHelper::money($item->avg_price) }}</td>
                <td style="text-align:right;color:#64748B;">{{ $item->nb_sales }}</td>
                <td style="text-align:right;font-weight:600;color:#12864B;">{{ \App\Helpers\FormatHelper::money($item->total_revenue) }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center;padding:40px;color:#64748B;">Aucune vente sur la période</td></tr>
            @endforelse
        </tbody>
        @if($items->count())
        <tfoot>
            <tr style="border-top:2px solid #E2E8F0;background:#F8FAFC;font-weight:700;">
                <td colspan="2">TOTAL</td>
                <td style="text-align:right">{{ \App\Helpers\FormatHelper::number($items->sum('total_qty')) }}</td>
                <td></td>
                <td style="text-align:right">{{ $items->sum('nb_sales') }}</td>
                <td style="text-align:right;color:#12864B;">{{ \App\Helpers\FormatHelper::money($items->sum('total_revenue')) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>
@endsection
