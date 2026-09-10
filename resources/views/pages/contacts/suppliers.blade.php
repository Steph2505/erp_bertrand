@extends('layouts.app')
@section('title', 'Fournisseurs')
@section('breadcrumb')<span class="current">Fournisseurs</span>@endsection

@section('content')
<div x-data="{ showModal: false, editSupplier: null }">

<div class="page-header">
    <div class="page-header__title">
        <h2>Fournisseurs</h2>
        <p>{{ $suppliers->total() }} fournisseur(s)</p>
    </div>
    <div class="page-header__actions">
        <button @click="showModal = true; editSupplier = null" class="btn btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nouveau fournisseur
        </button>
    </div>
</div>

<form method="GET" class="table-wrapper" style="padding:16px 20px;margin-bottom:16px;">
    <div class="form-grid form-grid--3" style="gap:12px;align-items:flex-end;">
        <div class="form-group" style="margin-bottom:0;grid-column:span 2">
            <label>Recherche</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, email, téléphone..." class="form-control">
        </div>
        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn--primary">Filtrer</button>
            <a href="{{ route('suppliers.index') }}" class="btn btn--ghost">Réinitialiser</a>
        </div>
    </div>
</form>

<div class="table-wrapper">
    <table class="data-table">
        <thead><tr>
            <th>Nom</th>
            <th>Société</th>
            <th>Téléphone</th>
            <th>Email</th>
            <th>Total achats</th>
            <th>Statut</th>
            <th style="text-align:right">Actions</th>
        </tr></thead>
        <tbody>
            @forelse($suppliers as $supplier)
                <tr>
                    <td style="font-weight:500">
                        <a href="{{ route('suppliers.show', $supplier) }}" style="color:inherit;text-decoration:none;"
                           onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                            {{ $supplier->name }}
                        </a>
                    </td>
                    <td>{{ $supplier->company ?? '—' }}</td>
                    <td>{{ $supplier->phone ?? '—' }}</td>
                    <td style="font-size:12px;color:#64748B;">{{ $supplier->email ?? '—' }}</td>
                    <td style="font-weight:600;color:#3b82f6;">{{ \App\Helpers\FormatHelper::money($supplier->purchases_sum_total ?? 0) }}</td>
                    <td><span class="badge badge--{{ $supplier->is_active ? 'green' : 'gray' }}">{{ $supplier->is_active ? 'Actif' : 'Inactif' }}</span></td>
                    <td>
                        <div class="data-table__actions">
                            <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn--ghost btn--sm btn--icon" title="Voir le détail">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            </a>
                            <button @click="editSupplier = {{ $supplier->toJson() }}; showModal = true"
                                class="btn btn--ghost btn--sm btn--icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                            </button>
                            <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirm('Supprimer ce fournisseur ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn--danger btn--sm btn--icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;padding:40px;color:#64748B;">Aucun fournisseur</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="table-wrapper__footer">
        <span>{{ $suppliers->firstItem() ?? 0 }}–{{ $suppliers->lastItem() ?? 0 }} sur {{ $suppliers->total() }}</span>
        {{ $suppliers->withQueryString()->links() }}
    </div>
</div>

{{-- Modal --}}
<div class="modal-overlay" x-show="showModal" x-cloak @click.self="showModal = false" x-transition>
    <div class="modal modal--md">
        <div class="modal__header">
            <h3 x-text="editSupplier ? 'Modifier le fournisseur' : 'Nouveau fournisseur'"></h3>
            <button class="modal__close" @click="showModal = false">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form :action="editSupplier ? '/suppliers/'+editSupplier.id : '{{ route('suppliers.store') }}'" method="POST" class="modal__body">
            @csrf
            <template x-if="editSupplier"><input type="hidden" name="_method" value="PUT"></template>
            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label>Nom <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" :value="editSupplier ? editSupplier.name : ''" required>
                </div>
                <div class="form-group">
                    <label>Société</label>
                    <input type="text" name="company" class="form-control" :value="editSupplier ? editSupplier.company : ''">
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="text" name="phone" class="form-control" :value="editSupplier ? editSupplier.phone : ''">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" :value="editSupplier ? editSupplier.email : ''">
                </div>
                <div class="form-group" style="grid-column:span 2">
                    <label>Adresse</label>
                    <input type="text" name="address" class="form-control" :value="editSupplier ? editSupplier.address : ''">
                </div>
                <div class="form-group">
                    <label>Solde initial ({{ $currency }})</label>
                    <input type="number" name="opening_balance" min="0" class="form-control" :value="editSupplier ? editSupplier.opening_balance : '0'">
                </div>
            </div>
            <div class="modal__footer" style="padding:0;border:none;margin-top:8px;display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" @click="showModal = false" class="btn btn--ghost">Annuler</button>
                <button type="submit" class="btn btn--primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection
