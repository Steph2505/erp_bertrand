@extends('layouts.app')
@section('title', 'Représentants')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Représentants</span>@endsection

@section('content')
<div x-data="reportAjax('{{ route('reports.api.agents') }}', {date_from: '{{ $from }}', date_to: '{{ $to }}'})" x-init="fetch()">
<div class="page-header">
    <div class="page-header__title"><h2>Performance des vendeurs</h2><p>CA réalisé par agent sur la période</p></div>
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
            <strong>Classement des vendeurs</strong>
            <span style="font-size:13px;color:#64748B;">CA total : <strong x-text="formatMoney(data.grand_total)"></strong></span>
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
                <template x-if="!loading && (data.agents ?? []).length === 0">
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:#64748B;">Aucune vente confirmée sur la période</td></tr>
                </template>
                <template x-for="(agent, i) in (data.agents ?? [])" :key="agent.name">
                    <tr>
                        <td style="color:#64748B;font-weight:700;" x-text="'#' + (i+1)"></td>
                        <td><strong x-text="agent.name"></strong></td>
                        <td style="text-align:right" x-text="agent.nb_sales"></td>
                        <td style="text-align:right;color:#64748B;" x-text="formatMoney(agent.direct_revenue)"></td>
                        <td style="text-align:right;color:#64748B;" x-text="formatMoney(agent.pos_revenue)"></td>
                        <td style="text-align:right;font-weight:700;color:#12864B;" x-text="formatMoney(agent.total_revenue)"></td>
                        <td style="text-align:right;">
                            <div style="display:flex;align-items:center;gap:6px;justify-content:flex-end;">
                                <div style="width:60px;height:6px;background:#E2E8F0;border-radius:3px;overflow:hidden;">
                                    <div :style="'height:100%;background:#1749B3;border-radius:3px;width:' + agent.share + '%'"></div>
                                </div>
                                <span style="font-size:12px;color:#64748B;" x-text="agent.share + '%'"></span>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
            <tfoot x-show="(data.agents ?? []).length > 1">
                <tr style="border-top:2px solid #E2E8F0;background:#F8FAFC;font-weight:700;">
                    <td colspan="2">TOTAL</td>
                    <td style="text-align:right" x-text="data.sum_nb_sales"></td>
                    <td style="text-align:right" x-text="formatMoney(data.sum_direct)"></td>
                    <td style="text-align:right" x-text="formatMoney(data.sum_pos)"></td>
                    <td style="text-align:right;color:#12864B;" x-text="formatMoney(data.grand_total)"></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
</div>
@include('components.report-ajax-script')
@endsection
