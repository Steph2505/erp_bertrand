@extends('layouts.app')
@section('title', 'Rapport fiscal')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Rapport fiscal</span>@endsection

@section('content')
<div x-data="reportAjax('{{ route('reports.api.tax') }}', {date_from: '{{ $from }}', date_to: '{{ $to }}'})" x-init="fetch()">
<div class="page-header">
    <div class="page-header__title"><h2>Rapport fiscal</h2><p>TVA collectée et déductible</p></div>
</div>

<div class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0"><label>Du</label><input type="date" x-model="filters.date_from" @change="fetch()" class="form-control"></div>
        <div class="form-group" style="margin:0"><label>Au</label><input type="date" x-model="filters.date_to" @change="fetch()" class="form-control"></div>
        <button type="button" @click="filters={date_from:'{{ $from }}',date_to:'{{ $to }}'}; fetch()" class="btn btn--ghost">Réinitialiser</button>
    </div>
</div>

<div class="stat-grid" style="margin-bottom:24px;position:relative;">
    <div x-show="loading" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
        <svg style="width:28px;height:28px;color:#1749B3;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
    </div>
    <div class="stat-card">
        <div class="stat-card__info"><div class="stat-card__label">TVA collectée</div><div class="stat-card__value" style="color:#12864B;" x-text="formatMoney(data.total_collected)"></div><div class="stat-card__trend stat-card__trend--flat">Sur ventes confirmées</div></div>
        <div class="stat-card__icon stat-card__icon--green"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info"><div class="stat-card__label">TVA déductible</div><div class="stat-card__value" style="color:#1749B3;" x-text="formatMoney(data.total_deductible)"></div><div class="stat-card__trend stat-card__trend--flat">Sur achats confirmés</div></div>
        <div class="stat-card__icon stat-card__icon--blue"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">TVA nette à payer</div>
            <div class="stat-card__value" :style="'color:' + ((data.tax_due ?? 0)>=0?'#C4231A':'#12864B')" x-text="formatMoney(data.tax_due)"></div>
            <div class="stat-card__trend" :class="(data.tax_due ?? 0)>=0?'stat-card__trend--down':'stat-card__trend--up'">Collectée – Déductible</div>
        </div>
        <div class="stat-card__icon" :class="(data.tax_due ?? 0)>=0?'stat-card__icon--red':'stat-card__icon--green'"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971Z"/></svg></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    <div class="table-wrapper">
        <div class="table-wrapper__header"><strong style="color:#12864B;">TVA collectée (ventes)</strong></div>
        <table class="data-table">
            <thead><tr><th>Taux</th><th style="text-align:right">Base HT</th><th style="text-align:right">TVA</th></tr></thead>
            <tbody>
                <template x-if="!loading && (data.tax_collected ?? []).length === 0">
                    <tr><td colspan="3" style="text-align:center;padding:24px;color:#64748B;">Aucune TVA sur la période</td></tr>
                </template>
                <template x-for="row in (data.tax_collected ?? [])" :key="row.tax_rate">
                    <tr>
                        <td><span class="badge badge--green" x-text="row.tax_rate + '%'"></span></td>
                        <td style="text-align:right" x-text="formatMoney(row.base)"></td>
                        <td style="text-align:right;font-weight:600;color:#12864B;" x-text="formatMoney(row.tax_amount)"></td>
                    </tr>
                </template>
                <tr x-show="(data.total_collected ?? 0) > 0" style="border-top:2px solid #E2E8F0;font-weight:700;">
                    <td>TOTAL</td><td></td><td style="text-align:right;color:#12864B;" x-text="formatMoney(data.total_collected)"></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="table-wrapper">
        <div class="table-wrapper__header"><strong style="color:#1749B3;">TVA déductible (achats)</strong></div>
        <table class="data-table">
            <thead><tr><th>Taux</th><th style="text-align:right">Base HT</th><th style="text-align:right">TVA</th></tr></thead>
            <tbody>
                <template x-if="!loading && (data.tax_deductible ?? []).length === 0">
                    <tr><td colspan="3" style="text-align:center;padding:24px;color:#64748B;">Aucune TVA sur la période</td></tr>
                </template>
                <template x-for="row in (data.tax_deductible ?? [])" :key="row.tax_rate">
                    <tr>
                        <td><span class="badge badge--blue" x-text="row.tax_rate + '%'"></span></td>
                        <td style="text-align:right" x-text="formatMoney(row.base)"></td>
                        <td style="text-align:right;font-weight:600;color:#1749B3;" x-text="formatMoney(row.tax_amount)"></td>
                    </tr>
                </template>
                <tr x-show="(data.total_deductible ?? 0) > 0" style="border-top:2px solid #E2E8F0;font-weight:700;">
                    <td>TOTAL</td><td></td><td style="text-align:right;color:#1749B3;" x-text="formatMoney(data.total_deductible)"></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
</div>
@include('components.report-ajax-script')
@endsection
