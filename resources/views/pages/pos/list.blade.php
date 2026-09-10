@extends('layouts.app')
@section('title', 'Ventes POS')
@section('breadcrumb')<span class="current">POS — Historique</span>@endsection

@section('content')
<div x-data="listPage('{{ route('pos.api.list') }}')" x-init="fetch()">

<div class="page-header">
    <div class="page-header__title">
        <h2>Ventes POS</h2>
        <p x-text="total + ' vente(s) POS'">—</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('pos.index') }}" class="btn btn--primary">Ouvrir le POS</a>
    </div>
</div>

<div class="table-wrapper sale-index__filters">
    <div class="filters-bar">
        <div class="form-group sale-index__filter-group" style="margin-bottom:0;">
            <label>Date</label>
            <input type="date" x-model="filters.date" @change="reset()" class="form-control" style="width:180px;">
        </div>
        <button x-show="hasFilters" @click="clearFilters()" class="btn btn--ghost btn--sm">✕ Tout</button>
        <span x-show="loading" class="text-muted text-sm">Chargement...</span>
    </div>
</div>

<div class="table-wrapper">
    <div class="table-wrapper__header">
        <strong>Ventes POS</strong>
    </div>
    <div style="position:relative;">
        <div x-show="loading && rows.length > 0" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
            <svg style="width:28px;height:28px;color:#1749B3;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>Référence</th><th>Client</th><th>Heure</th>
                <th>Total</th><th>Payé</th><th>Statut</th><th class="th-right">Actions</th>
            </tr></thead>
            <tbody>
                <template x-if="loading && rows.length === 0">
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:#64748B;">Chargement...</td></tr>
                </template>
                <template x-if="!loading && rows.length === 0">
                    <tr><td colspan="7" class="sale-index__empty-row"><td>Aucune vente POS</td></tr>
                </template>
                <template x-for="s in rows" :key="s.id">
                    <tr>
                        <td><a :href="'/pos/'+s.reference+'/ref'" class="pos-ref-link" x-text="s.reference"></a></td>
                        <td x-text="s.customer"></td>
                        <td class="text-muted text-md" x-text="s.time"></td>
                        <td><strong x-text="s.total"></strong></td>
                        <td class="text-md" x-text="s.amount_paid"></td>
                        <td><span x-html="s.pay_badge"></span></td>
                        <td>
                            <div class="data-table__actions">
                                <template x-if="s.payment_status !== 'paid'">
                                    <a :href="s.receipt_url" class="btn btn--primary btn--sm">Payer</a>
                                </template>
                                <a :href="s.receipt_url" target="_blank" class="btn btn--ghost btn--sm btn--icon" title="Ticket">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
    <div class="table-wrapper__footer">
        <span x-text="from + '–' + to + ' sur ' + total" class="text-muted text-sm"></span>
        <div style="display:flex;gap:4px;" x-show="lastPage > 1">
            <button @click="goTo(currentPage-1)" :disabled="currentPage<=1||loading" class="btn btn--ghost btn--sm btn--icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg></button>
            <template x-for="p in pages" :key="p"><button @click="p!=='…'&&goTo(p)" class="btn btn--sm" :class="p===currentPage?'btn--primary':'btn--ghost'" :disabled="p==='…'||loading" x-text="p" style="min-width:34px;justify-content:center;"></button></template>
            <button @click="goTo(currentPage+1)" :disabled="currentPage>=lastPage||loading" class="btn btn--ghost btn--sm btn--icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg></button>
        </div>
    </div>
</div>

</div>
@include('components.list-page-script')
@endsection
