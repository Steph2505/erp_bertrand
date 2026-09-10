@extends('layouts.app')
@section('title', 'Péremption du stock')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Péremption</span>@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title"><h2>Péremption du stock</h2><p>Produits avec date d'expiration</p></div>
    <div class="page-header__actions">
        <div class="filter-tabs">
            <a href="{{ route('reports.stock') }}" class="filter-tabs__btn">Stock</a>
            <a href="{{ route('reports.expiry') }}" class="filter-tabs__btn filter-tabs__btn--active">Péremption</a>
            <a href="{{ route('reports.stock-adjustment') }}" class="filter-tabs__btn">Ajustements</a>
        </div>
    </div>
</div>

<div class="stat-grid" style="margin-bottom:20px;">
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Expirés</div><div class="stat-card__value" style="color:#C4231A;">{{ $expired }}</div><div class="stat-card__trend stat-card__trend--down">À retirer des rayons</div></div><div class="stat-card__icon stat-card__icon--red"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg></div></div>
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Expirent ≤ 30j</div><div class="stat-card__value" style="color:#B45309;">{{ $warning }}</div><div class="stat-card__trend stat-card__trend--down">À surveiller</div></div><div class="stat-card__icon stat-card__icon--yellow"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg></div></div>
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">OK</div><div class="stat-card__value" style="color:#12864B;">{{ $ok }}</div><div class="stat-card__trend stat-card__trend--up">Péremption lointaine</div></div><div class="stat-card__icon stat-card__icon--green"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg></div></div>
</div>

<div class="table-wrapper">
    <div class="table-wrapper__header"><strong>Produits avec date de péremption</strong><span style="font-size:13px;color:#64748B;">{{ $products->count() }} produit(s)</span></div>
    <table class="data-table">
        <thead><tr><th>Produit</th><th>Catégorie</th><th style="text-align:right">Stock</th><th>Date expiration</th><th>Jours restants</th><th>Statut</th></tr></thead>
        <tbody>
            @forelse($products as $p)
            <tr>
                <td><strong>{{ $p->display_name }}</strong></td>
                <td>{{ $p->category ?? '—' }}</td>
                <td style="text-align:right">{{ \App\Helpers\FormatHelper::number($p->stock) }} {{ $p->unit }}</td>
                <td>{{ \App\Helpers\FormatHelper::date($p->expiry_date) }}</td>
                <td>
                    @if($p->days_left < 0)
                        <strong style="color:#C4231A;">{{ abs($p->days_left) }}j dépassé</strong>
                    @elseif($p->days_left === 0)
                        <strong style="color:#C4231A;">Aujourd'hui !</strong>
                    @else
                        <span style="color:{{ $p->days_left<=30?'#B45309':'#12864B' }}">{{ $p->days_left }}j</span>
                    @endif
                </td>
                <td>
                    @if($p->status==='expired') <span class="badge badge--red">Expiré</span>
                    @elseif($p->status==='warning') <span class="badge badge--yellow">Alerte</span>
                    @else <span class="badge badge--green">OK</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center;padding:40px;color:#64748B;">Aucun produit avec date de péremption</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
