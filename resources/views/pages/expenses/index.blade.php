@extends('layouts.app')
@section('title', 'Dépenses')
@section('breadcrumb')<span class="current">Dépenses</span>@endsection

@section('content')
<div x-data="{...listPage('{{ route('expenses.api.list') }}'), showModal: {{ request('new') ? 'true' : 'false' }}, editExpense: null, showCatModal: false}" x-init="filters = {category_id: '', date_from: '', date_to: ''}; fetch()">

<div class="page-header">
    <div class="page-header__title">
        <h2>Dépenses</h2>
        <p x-text="total + ' entrée(s)'">—</p>
    </div>
    <div class="page-header__actions">
        <button @click="showCatModal = true" class="btn btn--light">+ Catégorie</button>
        <button @click="showModal = true; editExpense = null" class="btn btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nouvelle dépense
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert--success mb-8">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert--danger mb-8">{{ $errors->first() }}</div>
@endif

{{-- Filtres --}}
<div class="table-wrapper expense-filter">
    <div class="form-grid form-grid--3 expense-filter__grid">
        <div class="form-group expense-filter__group">
            <label>Catégorie</label>
            <select x-model="filters.category_id" @change="reset()" class="form-select">
                <option value="">Toutes</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group expense-filter__group">
            <label>Date début</label>
            <input type="date" x-model="filters.date_from" @change="reset()" class="form-control">
        </div>
        <div class="form-group expense-filter__group">
            <label>Date fin</label>
            <input type="date" x-model="filters.date_to" @change="reset()" class="form-control">
        </div>
        <div class="expense-filter__actions">
            <button type="button" @click="clearFilters()" class="btn btn--ghost">Réinitialiser</button>
        </div>
    </div>
</div>

<div class="table-wrapper">
    <div class="table-wrapper__header">
        <strong>Liste des dépenses</strong>
        <span class="expense-count">Total : <strong x-text="extra.total_period ?? '—'">—</strong></span>
    </div>
    <div style="position:relative;">
        <div x-show="loading && rows.length > 0" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
            <svg style="width:28px;height:28px;color:#1749B3;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>Référence</th>
                <th>Catégorie</th>
                <th>Description</th>
                <th>Compte</th>
                <th>Date</th>
                <th>Montant</th>
                <th class="text-right">Actions</th>
            </tr></thead>
            <tbody>
                <template x-if="loading && rows.length === 0">
                    <tr><td colspan="7" class="expense-table__empty">Chargement...</td></tr>
                </template>
                <template x-if="!loading && rows.length === 0">
                    <tr><td colspan="7" class="expense-table__empty">Aucune entrée</td></tr>
                </template>
                <template x-for="row in rows" :key="row.type + '-' + row.id">
                    <tr>
                        <td class="expense-table__ref" x-text="row.reference"></td>
                        <td>
                            <template x-if="row.type === 'purchase'">
                                <span class="badge badge--blue">Achat fournisseur</span>
                            </template>
                            <template x-if="row.type !== 'purchase'">
                                <span x-text="row.category ?? '—'"></span>
                            </template>
                        </td>
                        <td x-text="row.description ?? '—'"></td>
                        <td x-text="row.account ?? '—'"></td>
                        <td class="expense-table__date" x-text="row.date_display"></td>
                        <td><strong class="expense-table__amount" x-text="row.amount_display"></strong></td>
                        <td>
                            <div class="data-table__actions">
                                <template x-if="row.type === 'purchase'">
                                    <a :href="row.show_url" class="btn btn--ghost btn--sm btn--icon" title="Voir l'achat">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                    </a>
                                </template>
                                <template x-if="row.type !== 'purchase'">
                                    <button @click="editExpense = row.raw; showModal = true"
                                        class="btn btn--ghost btn--sm btn--icon" title="Modifier">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                                    </button>
                                </template>
                                <template x-if="row.type !== 'purchase'">
                                    <form method="POST" :action="row.destroy_url" @submit.prevent="window.confirmDialog('Supprimer cette dépense ?', {variant:'danger', confirmLabel:'Supprimer'}).then(ok => ok && $el.submit())">
                                        @csrf @method('DELETE')
                                        <button class="btn btn--danger btn--sm btn--icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                        </button>
                                    </form>
                                </template>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
    <div class="table-wrapper__footer">
        <span x-text="from + '–' + to + ' sur ' + total"></span>
        <div style="display:flex;gap:4px;" x-show="lastPage > 1">
            <button @click="goTo(currentPage-1)" :disabled="currentPage<=1||loading" class="btn btn--ghost btn--sm btn--icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg></button>
            <template x-for="p in pages" :key="p"><button @click="p!=='…'&&goTo(p)" class="btn btn--sm" :class="p===currentPage?'btn--primary':'btn--ghost'" :disabled="p==='…'||loading" x-text="p" style="min-width:34px;justify-content:center;"></button></template>
            <button @click="goTo(currentPage+1)" :disabled="currentPage>=lastPage||loading" class="btn btn--ghost btn--sm btn--icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg></button>
        </div>
    </div>
</div>

{{-- Modal Dépense --}}
<div class="modal-overlay" x-show="showModal" x-cloak @click.self="showModal = false" x-transition>
    <div class="modal modal--md">
        <div class="modal__header">
            <h3 x-text="editExpense ? 'Modifier la dépense' : 'Nouvelle dépense'"></h3>
            <button class="modal__close" @click="showModal = false">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form :method="'POST'" :action="editExpense ? '/expenses/'+editExpense.id : '{{ route('expenses.store') }}'" method="POST" class="modal__body">
            @csrf
            <template x-if="editExpense"><input type="hidden" name="_method" value="PUT"></template>

            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label>Catégorie <span class="required">*</span></label>
                    <select name="expense_category_id" class="form-select" required>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" :selected="editExpense && editExpense.expense_category_id == {{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Compte de paiement <span class="required">*</span></label>
                    <select name="payment_account_id" class="form-select" required>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" :selected="editExpense && editExpense.payment_account_id == {{ $acc->id }}">{{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Date <span class="required">*</span></label>
                    <input type="date" name="expense_date" class="form-control"
                           :value="editExpense ? (editExpense.expense_date || '').substring(0, 10) : '{{ date('Y-m-d') }}'" required>
                </div>
                <div class="form-group">
                    <label>Montant ({{ $currency }}) <span class="required">*</span></label>
                    <input type="number" name="amount" min="1" step="1" class="form-control"
                           :value="editExpense ? editExpense.amount : ''" required>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-textarea" rows="2"
                          x-effect="$el.value = editExpense ? (editExpense.description || '') : ''"></textarea>
            </div>
            <div class="modal-footer-std">
                <button type="button" @click="showModal = false" class="btn btn--light">Annuler</button>
                <button type="submit" class="btn btn--primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Catégorie --}}
<div class="modal-overlay" x-show="showCatModal" x-cloak @click.self="showCatModal = false" x-transition>
    <div class="modal modal--sm">
        <div class="modal__header">
            <h3>Nouvelle catégorie</h3>
            <button class="modal__close" @click="showCatModal = false">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('expenses.categories.store') }}" class="modal__body">
            @csrf
            <div class="form-group">
                <label>Nom de la catégorie <span class="required">*</span></label>
                <input type="text" name="name" class="form-control" required placeholder="Ex: Loyer, Carburant...">
            </div>
            <div class="modal-footer-std">
                <button type="button" @click="showCatModal = false" class="btn btn--light">Annuler</button>
                <button type="submit" class="btn btn--primary">Créer</button>
            </div>
        </form>
    </div>
</div>

</div>
@include('components.list-page-script')
@endsection
