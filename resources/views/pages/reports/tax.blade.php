@extends('layouts.app')
@section('title', 'Rapport fiscal')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Rapport fiscal</span>@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title"><h2>Rapport fiscal</h2><p>TVA collectée et déductible</p></div>
</div>

<form method="GET" class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0"><label>Du</label><input type="date" name="date_from" value="{{ $from }}" class="form-control"></div>
        <div class="form-group" style="margin:0"><label>Au</label><input type="date" name="date_to" value="{{ $to }}" class="form-control"></div>
        <button class="btn btn--primary">Filtrer</button>
        <a href="{{ route('reports.tax') }}" class="btn btn--ghost">Réinitialiser</a>
    </div>
</form>

<div class="stat-grid" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-card__info"><div class="stat-card__label">TVA collectée</div><div class="stat-card__value" style="color:#22C55E;">{{ \App\Helpers\FormatHelper::money($totalCollected) }}</div><div class="stat-card__trend stat-card__trend--flat">Sur ventes confirmées</div></div>
        <div class="stat-card__icon stat-card__icon--green"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info"><div class="stat-card__label">TVA déductible</div><div class="stat-card__value" style="color:#3B82F6;">{{ \App\Helpers\FormatHelper::money($totalDeductible) }}</div><div class="stat-card__trend stat-card__trend--flat">Sur achats confirmés</div></div>
        <div class="stat-card__icon stat-card__icon--blue"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">TVA nette à payer</div>
            <div class="stat-card__value" style="color:{{ $taxDue>=0?'#EF4444':'#22C55E' }}">{{ \App\Helpers\FormatHelper::money($taxDue) }}</div>
            <div class="stat-card__trend stat-card__trend--{{ $taxDue>=0?'down':'up' }}">Collectée – Déductible</div>
        </div>
        <div class="stat-card__icon stat-card__icon--{{ $taxDue>=0?'red':'green' }}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971Z"/></svg></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    <div class="table-wrapper">
        <div class="table-wrapper__header"><strong style="color:#22C55E;">TVA collectée (ventes)</strong></div>
        <table class="data-table">
            <thead><tr><th>Taux</th><th style="text-align:right">Base HT</th><th style="text-align:right">TVA</th></tr></thead>
            <tbody>
                @forelse($taxCollected as $row)
                <tr>
                    <td><span class="badge badge--green">{{ $row->tax_rate }}%</span></td>
                    <td style="text-align:right">{{ \App\Helpers\FormatHelper::money($row->base) }}</td>
                    <td style="text-align:right;font-weight:600;color:#22C55E;">{{ \App\Helpers\FormatHelper::money($row->tax_amount) }}</td>
                </tr>
                @empty
                <tr><td colspan="3" style="text-align:center;padding:24px;color:#64748B;">Aucune TVA sur la période</td></tr>
                @endforelse
                @if($totalCollected > 0)
                <tr style="border-top:2px solid #E2E8F0;font-weight:700;">
                    <td>TOTAL</td><td></td><td style="text-align:right;color:#22C55E;">{{ \App\Helpers\FormatHelper::money($totalCollected) }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
    <div class="table-wrapper">
        <div class="table-wrapper__header"><strong style="color:#3B82F6;">TVA déductible (achats)</strong></div>
        <table class="data-table">
            <thead><tr><th>Taux</th><th style="text-align:right">Base HT</th><th style="text-align:right">TVA</th></tr></thead>
            <tbody>
                @forelse($taxDeductible as $row)
                <tr>
                    <td><span class="badge badge--blue">{{ $row->tax_rate }}%</span></td>
                    <td style="text-align:right">{{ \App\Helpers\FormatHelper::money($row->base) }}</td>
                    <td style="text-align:right;font-weight:600;color:#3B82F6;">{{ \App\Helpers\FormatHelper::money($row->tax_amount) }}</td>
                </tr>
                @empty
                <tr><td colspan="3" style="text-align:center;padding:24px;color:#64748B;">Aucune TVA sur la période</td></tr>
                @endforelse
                @if($totalDeductible > 0)
                <tr style="border-top:2px solid #E2E8F0;font-weight:700;">
                    <td>TOTAL</td><td></td><td style="text-align:right;color:#3B82F6;">{{ \App\Helpers\FormatHelper::money($totalDeductible) }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
