@extends('layouts.app')

@section('title', 'Rapport de dépenses')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Dépenses</span>@endsection

@section('content')
<div x-data="{...reportAjax('{{ route('reports.api.expenses') }}', {date_from: '{{ $from }}', date_to: '{{ $to }}', category: ''}), categoryOptions: @js($categoryOptions)}" x-init="fetch()">
<div class="page-header">
    <div class="page-header__title"><h2>Rapport de dépenses</h2><p>Charges d'exploitation et achats fournisseurs payés, par catégorie</p></div>
    <div class="page-header__actions">
        <a href="{{ route('expenses.index') }}" class="btn btn--ghost">Journal des dépenses</a>
    </div>
</div>

<div class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;overflow:visible;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0"><label>Du</label><input type="date" x-model="filters.date_from" @change="fetch()" class="form-control"></div>
        <div class="form-group" style="margin:0"><label>Au</label><input type="date" x-model="filters.date_to" @change="fetch()" class="form-control"></div>

        {{-- Catégorie : select personnalisé (autocomplete) --}}
        <div class="form-group" style="margin:0;min-width:220px;" x-data="{ search: '', open: false }" @click.outside="open=false">
            <label>Catégorie</label>
            <div class="autocomplete-wrap">
                <input type="text" x-model="search" @focus="open=true; search=''" @input="open=true"
                       :placeholder="filters.category || 'Toutes les catégories...'" class="form-control" autocomplete="off">
                <div x-show="open" x-transition class="autocomplete-dropdown">
                    <div @mousedown.prevent="filters.category=''; search=''; open=false; fetch()"
                         class="autocomplete-dropdown__item" style="color:#64748B;">
                        Toutes les catégories
                    </div>
                    <template x-for="c in categoryOptions.filter(c => c.toLowerCase().includes(search.toLowerCase()))" :key="c">
                        <div @mousedown.prevent="filters.category=c; search=c; open=false; fetch()"
                             class="autocomplete-dropdown__item"
                             :style="filters.category===c?'background:#eff6ff;color:#3b82f6;font-weight:600':''">
                            <span x-text="c"></span>
                        </div>
                    </template>
                    <template x-if="categoryOptions.filter(c => c.toLowerCase().includes(search.toLowerCase())).length === 0">
                        <div class="autocomplete-dropdown__empty">Aucun résultat</div>
                    </template>
                </div>
            </div>
        </div>

        <button type="button" @click="filters={date_from:'{{ $from }}',date_to:'{{ $to }}',category:''}; fetch()" class="btn btn--ghost">Réinitialiser</button>
    </div>
</div>

<div class="table-wrapper">
    <div style="position:relative;">
        <div x-show="loading" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
            <svg style="width:28px;height:28px;color:#1749B3;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        </div>
        <div class="table-wrapper__header">
            <strong>Dépenses par catégorie</strong>
            <span style="font-size:13px;color:#64748B;">Total : <strong x-text="formatMoney(data.total)"></strong></span>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>Catégorie</th>
                <th style="text-align:right">Montant</th>
            </tr></thead>
            <tbody>
                <template x-if="!loading && (data.rows ?? []).length === 0">
                    <tr><td colspan="2" style="text-align:center;padding:24px;color:#64748B;">Aucune dépense sur la période</td></tr>
                </template>
                <template x-for="row in (data.rows ?? [])" :key="row.category">
                    <tr>
                        <td x-text="row.category"></td>
                        <td style="text-align:right;font-weight:600;color:#C4231A;" x-text="formatMoney(row.amount)"></td>
                    </tr>
                </template>
            </tbody>
            <tfoot x-show="(data.rows ?? []).length > 0">
                <tr style="border-top:2px solid #E2E8F0;background:#F8FAFC;font-weight:700;">
                    <td>TOTAL</td>
                    <td style="text-align:right" x-text="formatMoney(data.total)"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
</div>
@include('components.report-ajax-script')
@endsection
