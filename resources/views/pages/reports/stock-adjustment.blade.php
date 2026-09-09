@extends('layouts.app')
@section('title', 'Ajustements de stock')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Ajustements</span>@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title"><h2>Ajustements de stock</h2><p>Mouvements manuels sur la période</p></div>
    <div class="page-header__actions">
        <div class="filter-tabs">
            <a href="{{ route('reports.stock') }}" class="filter-tabs__btn">Stock</a>
            <a href="{{ route('reports.expiry') }}" class="filter-tabs__btn">Péremption</a>
            <a href="{{ route('reports.stock-adjustment') }}" class="filter-tabs__btn filter-tabs__btn--active">Ajustements</a>
        </div>
    </div>
</div>

<form method="GET" class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0"><label>Du</label><input type="date" name="date_from" value="{{ $from }}" class="form-control"></div>
        <div class="form-group" style="margin:0"><label>Au</label><input type="date" name="date_to" value="{{ $to }}" class="form-control"></div>
        <button class="btn btn--primary">Filtrer</button>
        <a href="{{ route('reports.stock-adjustment') }}" class="btn btn--ghost">Réinitialiser</a>
    </div>
</form>

<div class="stat-grid" style="margin-bottom:20px;">
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Unités ajoutées</div><div class="stat-card__value" style="color:#22C55E;">+{{ \App\Helpers\FormatHelper::number($totalAdded) }}</div><div class="stat-card__trend stat-card__trend--up">Entrées manuelles</div></div><div class="stat-card__icon stat-card__icon--green"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg></div></div>
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Unités déduites</div><div class="stat-card__value" style="color:#EF4444;">−{{ \App\Helpers\FormatHelper::number($totalDeducted) }}</div><div class="stat-card__trend stat-card__trend--down">Sorties manuelles</div></div><div class="stat-card__icon stat-card__icon--red"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg></div></div>
</div>

<div class="table-wrapper">
    <div class="table-wrapper__header"><strong>Mouvements d'ajustement</strong><span style="font-size:13px;color:#64748B;">{{ $movements->total() }} mouvement(s)</span></div>
    <table class="data-table">
        <thead><tr><th>Date</th><th>Produit</th><th>Entrepôt</th><th>Type</th><th style="text-align:right">Quantité</th><th>Note</th><th>Par</th></tr></thead>
        <tbody>
            @forelse($movements as $m)
            <tr>
                <td style="font-size:12px;color:#64748B;">{{ \App\Helpers\FormatHelper::date($m->created_at) }}</td>
                <td><strong>{{ $m->product?->display_name ?? '—' }}</strong></td>
                <td>{{ $m->warehouse?->name ?? '—' }}</td>
                <td><span class="badge badge--{{ $m->type==='addition'?'green':($m->type==='subtraction'?'red':'gray') }}">{{ ucfirst($m->type) }}</span></td>
                <td style="text-align:right;font-weight:600;color:{{ $m->quantity>0?'#22C55E':'#EF4444' }}">
                    {{ $m->quantity > 0 ? '+' : '' }}{{ \App\Helpers\FormatHelper::number($m->quantity) }}
                </td>
                <td style="font-size:12px;color:#64748B;">{{ $m->note ?? '—' }}</td>
                <td style="font-size:12px;">{{ $m->createdBy?->name ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;padding:40px;color:#64748B;">Aucun ajustement sur la période</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="table-wrapper__footer">
        <span>{{ $movements->firstItem()??0 }}–{{ $movements->lastItem()??0 }} sur {{ $movements->total() }}</span>
        {{ $movements->withQueryString()->links() }}
    </div>
</div>
@endsection
