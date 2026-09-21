@extends('layouts.app')
@section('title', 'Achat par article')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Achat article</span>@endsection

@section('content')
<div x-data="reportAjax('{{ route('reports.api.product-purchase') }}', {date_from: '{{ $from }}', date_to: '{{ $to }}'})" x-init="fetch()">
<div class="page-header">
    <div class="page-header__title"><h2>Achat par article</h2><p>Volume d'achat par article sur la période</p></div>
    <div class="page-header__actions">
        <div class="filter-tabs">
            <a href="{{ route('reports.product-sale') }}" class="filter-tabs__btn">Vente article</a>
            <a href="{{ route('reports.product-purchase') }}" class="filter-tabs__btn filter-tabs__btn--active">Achat article</a>
        </div>
    </div>
</div>

<div class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0"><label>Du</label><input type="date" x-model="filters.date_from" @change="fetch()" class="form-control"></div>
        <div class="form-group" style="margin:0"><label>Au</label><input type="date" x-model="filters.date_to" @change="fetch()" class="form-control"></div>
        <button type="button" @click="filters={date_from:'{{ $from }}',date_to:'{{ $to }}'}; fetch()" class="btn btn--ghost">Réinitialiser</button>
    </div>
</div>

<div class="table-wrapper">
    <div style="position:relative;">
        <div x-show="loading" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
            <svg style="width:28px;height:28px;color:#1749B3;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        </div>
        <div class="table-wrapper__header">
            <strong>Achats par article — classement montant</strong>
            <span style="font-size:13px;color:#64748B;">Total achats : <strong x-text="formatMoney(data.sum_amount)"></strong></span>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>#</th><th>Article</th><th>Type</th>
                <th style="text-align:right">Qté achetée</th>
                <th style="text-align:right">Prix moy.</th>
                <th style="text-align:right">Nb bons</th>
                <th style="text-align:right">Total</th>
            </tr></thead>
            <tbody>
                <template x-if="!loading && (data.items ?? []).length === 0">
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:#64748B;">Aucun achat sur la période</td></tr>
                </template>
                <template x-for="(item, i) in (data.items ?? [])" :key="item.item_name + item.item_type">
                    <tr>
                        <td style="color:#64748B;font-weight:600;" x-text="'#' + (i+1)"></td>
                        <td><strong x-text="item.item_name"></strong></td>
                        <td><span class="badge" :class="item.item_type==='pack'?'badge--pack':'badge--gray'" x-text="item.item_type==='pack'?'Pack':'Article'"></span></td>
                        <td style="text-align:right" x-text="formatNumber(item.total_qty)"></td>
                        <td style="text-align:right;color:#64748B;" x-text="formatMoney(item.avg_price)"></td>
                        <td style="text-align:right;color:#64748B;" x-text="item.nb_purchases"></td>
                        <td style="text-align:right;font-weight:600;color:#1749B3;" x-text="formatMoney(item.total_amount)"></td>
                    </tr>
                </template>
            </tbody>
            <tfoot x-show="(data.items ?? []).length > 0">
                <tr style="border-top:2px solid #E2E8F0;background:#F8FAFC;font-weight:700;">
                    <td colspan="3">TOTAL</td>
                    <td style="text-align:right" x-text="formatNumber(data.sum_qty)"></td>
                    <td></td>
                    <td style="text-align:right" x-text="data.sum_nb_purchases"></td>
                    <td style="text-align:right;color:#1749B3;" x-text="formatMoney(data.sum_amount)"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
</div>
@include('components.report-ajax-script')
@endsection
