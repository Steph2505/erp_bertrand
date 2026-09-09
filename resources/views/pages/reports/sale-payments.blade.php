@extends('layouts.app')
@section('title', 'Paiements de vente')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Paiements vente</span>@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title"><h2>Paiements de vente</h2><p>Encaissements reçus des clients</p></div>
    <div class="page-header__actions">
        <div class="filter-tabs">
            <a href="{{ route('reports.purchase-payments') }}" class="filter-tabs__btn">Achats</a>
            <a href="{{ route('reports.sale-payments') }}" class="filter-tabs__btn filter-tabs__btn--active">Ventes</a>
        </div>
    </div>
</div>

<form method="GET" class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0"><label>Du</label><input type="date" name="date_from" value="{{ $from }}" class="form-control"></div>
        <div class="form-group" style="margin:0"><label>Au</label><input type="date" name="date_to" value="{{ $to }}" class="form-control"></div>
        <button class="btn btn--primary">Filtrer</button>
        <a href="{{ route('reports.sale-payments') }}" class="btn btn--ghost">Réinitialiser</a>
    </div>
</form>

<div class="table-wrapper">
    <div class="table-wrapper__header">
        <strong>Encaissements</strong>
        <span style="font-size:13px;color:#64748B;">Total : <strong style="color:#22C55E;">{{ \App\Helpers\FormatHelper::money($total) }}</strong></span>
    </div>
    <table class="data-table">
        <thead><tr>
            <th>Date</th><th>Référence</th><th>Vente</th><th>Client</th><th>Mode</th><th>Compte</th><th style="text-align:right">Montant</th>
        </tr></thead>
        <tbody>
            @forelse($payments as $pmt)
            @php $sale = $sales[$pmt->payable_id] ?? null; @endphp
            <tr>
                <td>{{ \App\Helpers\FormatHelper::date($pmt->payment_date) }}</td>
                <td style="font-size:12px;color:#64748B;font-weight:600;">{{ $pmt->reference }}</td>
                <td>
                    @if($sale)
                        <a href="{{ route('sales.show', $sale->id) }}" style="color:#4CBB17;font-weight:500;">{{ $sale->reference }}</a>
                    @else —
                    @endif
                </td>
                <td>{{ $sale?->customer?->name ?? 'Client comptoir' }}</td>
                <td><span class="badge badge--gray">{{ $pmt->payment_method }}</span></td>
                <td style="font-size:12px;color:#64748B;">{{ $pmt->paymentAccount?->name ?? '—' }}</td>
                <td style="text-align:right;font-weight:600;color:#22C55E;">{{ \App\Helpers\FormatHelper::money($pmt->amount) }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;padding:40px;color:#64748B;">Aucun encaissement sur la période</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="table-wrapper__footer">
        <span>{{ $payments->firstItem()??0 }}–{{ $payments->lastItem()??0 }} sur {{ $payments->total() }}</span>
        {{ $payments->withQueryString()->links() }}
    </div>
</div>
@endsection
