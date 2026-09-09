@extends('layouts.app')
@section('title', 'Achats')
@section('breadcrumb')<span class="current">Achats</span>@endsection

@section('content')
<div x-data="listPage('{{ route('purchases.api.list') }}')" x-init="fetch()">

<div class="page-header">
    <div class="page-header__title">
        <h2>Achats</h2>
        <p x-text="total + ' achat(s)'">—</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('purchases.create') }}" class="btn btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nouvel achat
        </a>
    </div>
</div>

<div class="table-wrapper purchase-filters">
    <div class="purchase-filters__grid">
        <div class="form-group purchase-filters__group">
            <label>Recherche</label>
            <input type="text" x-model="filters.search" @input.debounce.400ms="reset()" class="form-control" placeholder="Référence...">
        </div>
        <div class="form-group purchase-filters__group">
            <label>Fournisseur</label>
            <select x-model="filters.supplier_id" @change="reset()" class="form-select">
                <option value="">Tous</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group purchase-filters__group">
            <label>Statut</label>
            <select x-model="filters.status" @change="reset()" class="form-select">
                <option value="">Tous</option>
                <option value="draft">Brouillon</option>
                <option value="confirmed">Confirmé</option>
            </select>
        </div>
        <div style="display:flex;gap:8px;align-items:flex-end;">
            <button x-show="hasFilters" @click="clearFilters()" class="btn btn--ghost btn--sm">✕ Effacer</button>
            <span x-show="loading" class="text-muted text-sm">Chargement...</span>
        </div>
    </div>
</div>

<div class="table-wrapper">
    <div style="position:relative;">
        <div x-show="loading && rows.length > 0" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
            <svg style="width:28px;height:28px;color:#4CBB17;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>Référence</th><th>Fournisseur</th><th>Date</th>
                <th>Statut</th><th>Paiement</th><th>Total</th><th class="th-right">Actions</th>
            </tr></thead>
            <tbody>
                <template x-if="loading && rows.length === 0">
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:#64748B;">Chargement...</td></tr>
                </template>
                <template x-if="!loading && rows.length === 0">
                    <tr><td colspan="7" class="purchase-index__empty">Aucun achat trouvé</td></tr>
                </template>
                <template x-for="p in rows" :key="p.id">
                    <tr>
                        <td><a :href="p.show_url" class="purchase-index__ref-link" x-text="p.reference"></a></td>
                        <td x-text="p.supplier"></td>
                        <td x-text="p.purchase_date"></td>
                        <td><span x-html="p.status_badge"></span></td>
                        <td><span x-html="p.pay_badge"></span></td>
                        <td><strong x-text="p.total"></strong></td>
                        <td>
                            <div class="data-table__actions">
                                <a :href="p.show_url" class="btn btn--ghost btn--sm btn--icon" title="Voir">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                </a>
                                <template x-if="p.status === 'draft'">
                                    <a :href="p.edit_url" class="btn btn--ghost btn--sm btn--icon" title="Modifier">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                                    </a>
                                </template>
                                <a :href="p.print_url" target="_blank" class="btn btn--ghost btn--sm btn--icon" title="Imprimer">
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
