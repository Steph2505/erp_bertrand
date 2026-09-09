@extends('layouts.app')
@section('title', 'Représentants')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Représentants</span>@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title"><h2>Performance des vendeurs</h2><p>CA réalisé par agent sur la période</p></div>
</div>

<form method="GET" class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0"><label>Du</label><input type="date" name="date_from" value="{{ $from }}" class="form-control"></div>
        <div class="form-group" style="margin:0"><label>Au</label><input type="date" name="date_to" value="{{ $to }}" class="form-control"></div>
        <button class="btn btn--primary">Filtrer</button>
        <a href="{{ route('reports.agents') }}" class="btn btn--ghost">Réinitialiser</a>
    </div>
</form>

@php $grandTotal = $agents->sum('total_revenue'); @endphp

<div class="table-wrapper">
    <div class="table-wrapper__header">
        <strong>Classement des vendeurs</strong>
        <span style="font-size:13px;color:#64748B;">CA total : <strong>{{ \App\Helpers\FormatHelper::money($grandTotal) }}</strong></span>
    </div>
    <table class="data-table">
        <thead><tr>
            <th>#</th><th>Vendeur</th>
            <th style="text-align:right">Nb ventes</th>
            <th style="text-align:right">CA direct</th>
            <th style="text-align:right">CA POS</th>
            <th style="text-align:right">CA total</th>
            <th style="text-align:right">Part</th>
        </tr></thead>
        <tbody>
            @forelse($agents as $i => $agent)
            @php $share = $grandTotal > 0 ? ($agent->total_revenue / $grandTotal * 100) : 0; @endphp
            <tr>
                <td style="color:#64748B;font-weight:700;">#{{ $i+1 }}</td>
                <td><strong>{{ $agent->name }}</strong></td>
                <td style="text-align:right">{{ $agent->nb_sales }}</td>
                <td style="text-align:right;color:#64748B;">{{ \App\Helpers\FormatHelper::money($agent->direct_revenue) }}</td>
                <td style="text-align:right;color:#64748B;">{{ \App\Helpers\FormatHelper::money($agent->pos_revenue) }}</td>
                <td style="text-align:right;font-weight:700;color:#22C55E;">{{ \App\Helpers\FormatHelper::money($agent->total_revenue) }}</td>
                <td style="text-align:right;">
                    <div style="display:flex;align-items:center;gap:6px;justify-content:flex-end;">
                        <div style="width:60px;height:6px;background:#E2E8F0;border-radius:3px;overflow:hidden;">
                            <div style="width:{{ $share }}%;height:100%;background:#4CBB17;border-radius:3px;"></div>
                        </div>
                        <span style="font-size:12px;color:#64748B;">{{ number_format($share,1) }}%</span>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;padding:40px;color:#64748B;">Aucune vente confirmée sur la période</td></tr>
            @endforelse
        </tbody>
        @if($agents->count() > 1)
        <tfoot>
            <tr style="border-top:2px solid #E2E8F0;background:#F8FAFC;font-weight:700;">
                <td colspan="2">TOTAL</td>
                <td style="text-align:right">{{ $agents->sum('nb_sales') }}</td>
                <td style="text-align:right">{{ \App\Helpers\FormatHelper::money($agents->sum('direct_revenue')) }}</td>
                <td style="text-align:right">{{ \App\Helpers\FormatHelper::money($agents->sum('pos_revenue')) }}</td>
                <td style="text-align:right;color:#22C55E;">{{ \App\Helpers\FormatHelper::money($grandTotal) }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>
@endsection
