@extends('layouts.app')
@section('title', 'Fournisseurs & Clients')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Contacts</span>@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title"><h2>Fournisseurs & Clients</h2><p>Top contacts par volume</p></div>
</div>

<form method="GET" class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0"><label>Du</label><input type="date" name="date_from" value="{{ $from }}" class="form-control"></div>
        <div class="form-group" style="margin:0"><label>Au</label><input type="date" name="date_to" value="{{ $to }}" class="form-control"></div>
        <button class="btn btn--primary">Filtrer</button>
        <a href="{{ route('reports.contacts') }}" class="btn btn--ghost">Réinitialiser</a>
    </div>
</form>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

    <div class="table-wrapper">
        <div class="table-wrapper__header">
            <strong style="color:#12864B;">Top clients</strong>
            <span style="font-size:13px;color:#64748B;">{{ $topCustomers->count() }} client(s)</span>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>#</th><th>Client</th>
                <th style="text-align:right">Nb ventes</th>
                <th style="text-align:right">CA total</th>
            </tr></thead>
            <tbody>
                @forelse($topCustomers as $i => $c)
                <tr>
                    <td style="color:#64748B;font-weight:700;">#{{ $i+1 }}</td>
                    <td>
                        <a href="{{ route('customers.show', $c->id) }}" style="color:#1749B3;font-weight:500;">{{ $c->name }}</a>
                        @if($c->phone) <span style="font-size:11px;color:#94A3B8;">{{ $c->phone }}</span> @endif
                    </td>
                    <td style="text-align:right">{{ $c->nb_sales }}</td>
                    <td style="text-align:right;font-weight:600;color:#12864B;">{{ \App\Helpers\FormatHelper::money($c->total_sales) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center;padding:24px;color:#64748B;">Aucune vente sur la période</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-wrapper">
        <div class="table-wrapper__header">
            <strong style="color:#1749B3;">Top fournisseurs</strong>
            <span style="font-size:13px;color:#64748B;">{{ $topSuppliers->count() }} fournisseur(s)</span>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>#</th><th>Fournisseur</th>
                <th style="text-align:right">Nb achats</th>
                <th style="text-align:right">Total</th>
            </tr></thead>
            <tbody>
                @forelse($topSuppliers as $i => $s)
                <tr>
                    <td style="color:#64748B;font-weight:700;">#{{ $i+1 }}</td>
                    <td>
                        <a href="{{ route('suppliers.show', $s->id) }}" style="color:#1749B3;font-weight:500;">{{ $s->name }}</a>
                    </td>
                    <td style="text-align:right">{{ $s->nb_purchases }}</td>
                    <td style="text-align:right;font-weight:600;color:#1749B3;">{{ \App\Helpers\FormatHelper::money($s->total_purchases) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center;padding:24px;color:#64748B;">Aucun achat sur la période</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
