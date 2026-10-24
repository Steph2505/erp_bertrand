@extends('layouts.app')
@section('title', 'Ventes par caissier')
@section('breadcrumb')<a title="Aller à : Rapports" href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Ventes par caissier</span>@endsection

@section('content')
<div x-data="reportAjax('{{ route('reports.api.cashiers') }}', {date_from: '{{ $from }}', date_to: '{{ $to }}', sort: '{{ $sort }}'})" x-init="fetch()">
    <div class="page-header">
        <div class="page-header__title">
            <h2>Ventes par caissier</h2>
            <p>Classement des caissiers selon le chiffre d'affaires net et le bénéfice brut estimé</p>
        </div>
    </div>

    <div class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;">
        <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <div class="form-group" style="margin:0">
                <label>Du</label>
                <input type="date" x-model="filters.date_from" @change="fetch()" class="form-control">
            </div>
            <div class="form-group" style="margin:0">
                <label>Au</label>
                <input type="date" x-model="filters.date_to" @change="fetch()" class="form-control">
            </div>
            <div class="form-group" style="margin:0">
                <label>Classement</label>
                <select x-model="filters.sort" @change="fetch()" class="form-select">
                    <option value="revenue">Chiffre d'affaires net</option>
                    <option value="profit">Bénéfice brut estimé</option>
                    <option value="sales">Nombre de ventes</option>
                    <option value="name">Nom (A-Z)</option>
                </select>
            </div>
            <button title="Réinitialiser les dates et le classement" type="button"
                    @click="filters={date_from:'{{ $from }}',date_to:'{{ $to }}',sort:'revenue'}; fetch()"
                    class="btn btn--ghost">Réinitialiser</button>
        </div>
    </div>

    <div class="stat-grid" style="margin-bottom:16px;">
        <div class="stat-card">
            <div class="stat-card__info">
                <div class="stat-card__label">Chiffre d'affaires net</div>
                <div class="stat-card__value" style="color:#12864B;" x-text="formatMoney(data.totals?.net_revenue)"></div>
                <div class="stat-card__trend stat-card__trend--flat">Ventes moins retours</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card__info">
                <div class="stat-card__label">Bénéfice brut estimé</div>
                <div class="stat-card__value" style="color:#1749B3;" x-text="formatMoney(data.totals?.profit)"></div>
                <div class="stat-card__trend stat-card__trend--flat">CA net moins coût des articles</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card__info">
                <div class="stat-card__label">Ventes POS</div>
                <div class="stat-card__value" x-text="formatNumber(data.totals?.sales_count)"></div>
                <div class="stat-card__trend stat-card__trend--flat" x-text="formatNumber(data.totals?.sessions_count) + ' session(s) concernée(s)'"></div>
            </div>
        </div>
    </div>

    <div class="table-wrapper">
        <div style="position:relative;">
            <div x-show="loading" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
                <svg style="width:28px;height:28px;color:#1749B3;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
            </div>
            <div class="table-wrapper__header">
                <strong>Classement des caissiers</strong>
                <span style="font-size:13px;color:#64748B;" x-text="(data.rows ?? []).length + ' caissier(s)'"></span>
            </div>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead><tr>
                        <th>#</th>
                        <th>Caissier</th>
                        <th style="text-align:right">Sessions</th>
                        <th style="text-align:right">Ventes</th>
                        <th style="text-align:right">CA brut</th>
                        <th style="text-align:right">Retours</th>
                        <th style="text-align:right">CA net</th>
                        <th style="text-align:right">Coût</th>
                        <th style="text-align:right">Bénéfice brut estimé</th>
                        <th style="text-align:right">Coûts manquants</th>
                    </tr></thead>
                    <tbody>
                        <template x-if="!loading && (data.rows ?? []).length === 0">
                            <tr><td colspan="10" style="text-align:center;padding:40px;color:#64748B;">Aucune vente POS sur la période</td></tr>
                        </template>
                        <template x-for="(row, index) in (data.rows ?? [])" :key="row.cashier_name">
                            <tr>
                                <td style="color:#64748B;font-weight:700;" x-text="'#' + (index + 1)"></td>
                                <td>
                                    <strong x-text="row.cashier_name"></strong>
                                    <span x-show="row.top_seller" class="badge badge--green" style="margin-left:6px;">Meilleur CA</span>
                                    <span x-show="row.top_profit" class="badge badge--blue" style="margin-left:6px;">Meilleur bénéfice</span>
                                </td>
                                <td style="text-align:right" x-text="formatNumber(row.sessions_count)"></td>
                                <td style="text-align:right" x-text="formatNumber(row.sales_count)"></td>
                                <td style="text-align:right" x-text="formatMoney(row.gross_revenue)"></td>
                                <td style="text-align:right;color:#C4231A;" x-text="formatMoney(row.returned_total)"></td>
                                <td style="text-align:right;font-weight:600;color:#12864B;" x-text="formatMoney(row.net_revenue)"></td>
                                <td style="text-align:right;color:#64748B;" x-text="formatMoney(row.cost)"></td>
                                <td :style="'text-align:right;font-weight:600;color:' + (row.profit >= 0 ? '#12864B' : '#C4231A')" x-text="formatMoney(row.profit)"></td>
                                <td style="text-align:right">
                                    <span class="badge" :class="row.missing_cost_lines ? 'badge--yellow' : 'badge--green'"
                                          x-text="formatNumber(row.missing_cost_lines)"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot x-show="(data.rows ?? []).length > 0">
                        <tr style="border-top:2px solid #E2E8F0;background:#F8FAFC;font-weight:700;">
                            <td colspan="2">TOTAL</td>
                            <td style="text-align:right" x-text="formatNumber(data.totals?.sessions_count)"></td>
                            <td style="text-align:right" x-text="formatNumber(data.totals?.sales_count)"></td>
                            <td style="text-align:right" x-text="formatMoney(data.totals?.gross_revenue)"></td>
                            <td style="text-align:right;color:#C4231A;" x-text="formatMoney(data.totals?.returned_total)"></td>
                            <td style="text-align:right;color:#12864B;" x-text="formatMoney(data.totals?.net_revenue)"></td>
                            <td style="text-align:right" x-text="formatMoney(data.totals?.cost)"></td>
                            <td style="text-align:right;color:#12864B;" x-text="formatMoney(data.totals?.profit)"></td>
                            <td style="text-align:right" x-text="formatNumber(data.totals?.missing_cost_lines)"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <p style="font-size:12px;color:#64748B;margin-top:12px;line-height:1.5;">
        Le bénéfice est estimé à partir du CA net moins le coût enregistré des articles vendus. Les retours sont déduits du CA,
        mais le système ne conserve pas le détail des articles retournés pour ajuster le coût. Une ligne avec un coût manquant
        rend le bénéfice du caissier incomplet. Les ventes POS sans session apparaissent comme « Non attribué ».
    </p>
</div>
@include('components.report-ajax-script')
@endsection
