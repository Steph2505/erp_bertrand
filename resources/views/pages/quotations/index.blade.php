@extends('layouts.app')
@section('title', 'Devis')
@section('breadcrumb')<span class="current">Devis</span>@endsection

@section('content')
<div x-data="listPage('{{ route('quotations.api.list') }}')" x-init="fetch()">

<div class="page-header">
    <div class="page-header__title">
        <h2>Devis</h2>
        <p x-text="total + ' devis'">—</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('quotations.create') }}" class="btn btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nouveau devis
        </a>
    </div>
</div>

<div class="table-wrapper" style="margin-bottom:16px;padding:14px 20px;">
    <div class="filters-bar">
        <select x-model="filters.status" @change="reset()" class="form-select">
            <option value="">Tous statuts</option>
            <option value="draft">Brouillon</option>
            <option value="sent">Envoyé</option>
            <option value="accepted">Accepté</option>
            <option value="rejected">Refusé</option>
            <option value="expired">Expiré</option>
        </select>
        <select x-model="filters.customer_id" @change="reset()" class="form-select">
            <option value="">Tous les clients</option>
            @foreach($customers as $c)
                <option value="{{ $c->id }}">{{ $c->name }}</option>
            @endforeach
        </select>
        <button x-show="hasFilters" @click="clearFilters()" class="btn btn--ghost btn--sm">✕ Effacer</button>
        <span x-show="loading" class="text-muted text-sm">Chargement...</span>
    </div>
</div>

<div class="table-wrapper">
    <div style="position:relative;">
        <div x-show="loading && rows.length > 0" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
            <svg style="width:28px;height:28px;color:#4CBB17;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>Référence</th><th>Client</th><th>Date</th><th>Expiration</th>
                <th>Total</th><th>Statut</th><th class="th-right">Actions</th>
            </tr></thead>
            <tbody>
                <template x-if="loading && rows.length === 0">
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:#64748B;">Chargement...</td></tr>
                </template>
                <template x-if="!loading && rows.length === 0">
                    <tr><td colspan="7" class="quotation-table__empty">Aucun devis</td></tr>
                </template>
                <template x-for="q in rows" :key="q.id">
                    <tr>
                        <td class="quotation-table__ref" x-text="q.reference"></td>
                        <td x-text="q.customer"></td>
                        <td class="quotation-table__date" x-text="q.quotation_date"></td>
                        <td :class="q.expiry_past ? 'quotation-table__expiry-past' : 'quotation-table__expiry-ok'"
                            x-text="q.expiry_date ?? '—'"></td>
                        <td><strong x-text="q.total"></strong></td>
                        <td><span :class="'badge ' + q.status_class" x-text="q.status_label"></span></td>
                        <td>
                            <div class="data-table__actions">
                                <a :href="q.show_url" class="btn btn--ghost btn--sm btn--icon" title="Voir">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                </a>
                                <template x-if="q.convert_url">
                                    <form :action="q.convert_url" method="POST" @submit.prevent="confirm('Convertir en vente ?') && $el.submit()">
                                        @csrf
                                        <button class="btn btn--primary btn--sm" title="Convertir en vente">→ Vente</button>
                                    </form>
                                </template>
                                <form :action="q.destroy_url" method="POST" @submit.prevent="confirm('Supprimer ce devis ?') && $el.submit()">
                                    @csrf @method('DELETE')
                                    <button class="btn btn--danger btn--sm btn--icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    </button>
                                </form>
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
