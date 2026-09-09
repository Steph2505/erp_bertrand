@extends('layouts.app')
@section('title', 'Entrepôts')
@section('breadcrumb')
    <a href="{{ route('settings.company') }}">Paramètres</a>
    <span class="sep">/</span><span class="current">Entrepôts</span>
@endsection

@section('content')
<div x-data="{ showModal: false, editWh: null }">

<div class="page-header">
    <div class="page-header__title">
        <h2>Entrepôts / Sites</h2>
        <p>Lieux de stockage et points de vente</p>
    </div>
    <div class="page-header__actions">
        <button @click="showModal = true; editWh = null" class="btn btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nouvel entrepôt
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert--success" style="margin-bottom:16px;">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert--danger" style="margin-bottom:16px;">{{ $errors->first() }}</div>
@endif

<div class="stat-grid" style="margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Total entrepôts</div>
            <div class="stat-card__value">{{ $warehouses->count() }}</div>
            <div class="stat-card__trend stat-card__trend--flat">Tous statuts</div>
        </div>
        <div class="stat-card__icon stat-card__icon--blue">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z"/></svg>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Actifs</div>
            <div class="stat-card__value" style="color:#22C55E;">{{ $warehouses->where('is_active', true)->count() }}</div>
            <div class="stat-card__trend stat-card__trend--up">En service</div>
        </div>
        <div class="stat-card__icon stat-card__icon--green">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        </div>
    </div>
</div>

<div class="table-wrapper">
    <div class="table-wrapper__header"><strong>Liste des entrepôts</strong></div>
    <table class="data-table">
        <thead><tr>
            <th>Nom</th><th>Code</th><th>Adresse</th><th>Téléphone</th><th>Statut</th>
            <th style="text-align:right">Actions</th>
        </tr></thead>
        <tbody>
            @forelse($warehouses as $w)
            <tr>
                <td>
                    <strong>{{ $w->name }}</strong>
                    @if($w->id === $defaultWarehouseId)
                        <span class="badge badge--green" style="margin-left:6px;font-size:11px;">Par défaut</span>
                    @endif
                </td>
                <td>
                    <code style="font-size:11px;background:#F1F5F9;padding:2px 8px;border-radius:4px;font-weight:600;">
                        {{ $w->code }}
                    </code>
                </td>
                <td style="color:#64748B;font-size:13px;">{{ $w->address ?? '—' }}</td>
                <td style="color:#64748B;font-size:13px;">{{ $w->phone ?? '—' }}</td>
                <td>
                    <span class="badge badge--{{ $w->is_active ? 'green' : 'gray' }}">
                        {{ $w->is_active ? 'Actif' : 'Inactif' }}
                    </span>
                </td>
                <td>
                    <div class="data-table__actions">
                        @if($w->id !== $defaultWarehouseId)
                        <form method="POST" action="{{ route('settings.warehouses.set-default', $w) }}" style="display:inline;">
                            @csrf
                            <button class="btn btn--light btn--sm" style="font-size:12px;" title="Définir par défaut">★ Par défaut</button>
                        </form>
                        @endif
                        <button @click="editWh = {{ $w->toJson() }}; showModal = true"
                                class="btn btn--ghost btn--sm btn--icon" title="Modifier">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                        </button>
                        <form method="POST" action="{{ route('settings.warehouses.destroy', $w) }}"
                              onsubmit="return confirm('Supprimer cet entrepôt ?')">
                            @csrf @method('DELETE')
                            <button class="btn btn--danger btn--sm btn--icon" title="Supprimer">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center;padding:40px;color:#64748B;">Aucun entrepôt configuré</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Modal --}}
<div class="modal-overlay" x-show="showModal" x-cloak @click.self="showModal = false" x-transition>
    <div class="modal modal--md">
        <div class="modal__header">
            <h3 x-text="editWh ? 'Modifier l\'entrepôt' : 'Nouvel entrepôt'"></h3>
            <button class="modal__close" @click="showModal = false">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form :action="editWh ? '/settings/warehouses/'+editWh.id : '{{ route('settings.warehouses.store') }}'"
              method="POST" class="modal__body">
            @csrf
            <template x-if="editWh"><input type="hidden" name="_method" value="PUT"></template>

            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label>Nom <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" :value="editWh?.name ?? ''" required>
                </div>
                <div class="form-group" x-show="!editWh">
                    <label>Code unique</label>
                    <input type="text" name="code" class="form-control" placeholder="BTQ01, CUISINE…">
                    <span class="form-hint">Identifiant court, non modifiable après création</span>
                </div>
            </div>
            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label>Adresse</label>
                    <input type="text" name="address" class="form-control" :value="editWh?.address ?? ''">
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="text" name="phone" class="form-control" :value="editWh?.phone ?? ''">
                </div>
            </div>
            <template x-if="editWh">
                <label class="form-checkbox">
                    <input type="checkbox" name="is_active" value="1" :checked="editWh?.is_active">
                    Entrepôt actif
                </label>
            </template>

            <div class="modal__footer" style="padding:0;border:none;margin-top:16px;display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" @click="showModal = false" class="btn btn--ghost">Annuler</button>
                <button type="submit" class="btn btn--primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection
