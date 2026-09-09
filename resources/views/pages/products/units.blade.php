@extends('layouts.app')
@section('title', 'Unités de mesure')
@section('breadcrumb')
<a href="{{ route('products.index') }}">Produits</a>
<span class="current">Unités</span>
@endsection

@section('content')
<div x-data="{ showModal: false, editUnit: null }">

<div class="page-header">
    <div class="page-header__title">
        <h2>Unités de mesure</h2>
        <p>{{ $units->total() }} unité(s) configurée(s)</p>
    </div>
    <div class="page-header__actions">
        <button @click="showModal = true; editUnit = null" class="btn btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nouvelle unité
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert--success mb-16"><div class="alert__content">{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert--danger mb-16"><div class="alert__content">{{ session('error') }}</div></div>
@endif

<div class="table-wrapper">
    <table class="data-table">
        <thead><tr>
            <th>Nom</th>
            <th>Abréviation</th>
            <th>Nb produits</th>
            <th class="th-right">Actions</th>
        </tr></thead>
        <tbody>
            @forelse($units as $unit)
            <tr>
                <td class="table-cell--name">{{ $unit->name }}</td>
                <td><span class="badge badge--gray">{{ $unit->abbreviation }}</span></td>
                <td class="table-cell--muted">{{ $unit->products_count }}</td>
                <td>
                    <div class="data-table__actions">
                        <button @click="editUnit = {{ $unit->toJson() }}; showModal = true"
                            class="btn btn--ghost btn--sm btn--icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                        </button>
                        @if($unit->products_count === 0)
                        <form method="POST" action="{{ route('units.destroy', $unit) }}" onsubmit="return confirm('Supprimer cette unité ?')">
                            @csrf @method('DELETE')
                            <button class="btn btn--danger btn--sm btn--icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                            </button>
                        </form>
                        @else
                        <span class="in-use-label">En usage</span>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="table-empty-cell--sm">Aucune unité. Créez-en une.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="table-wrapper__footer">
        <span>{{ $units->firstItem() ?? 0 }}–{{ $units->lastItem() ?? 0 }} sur {{ $units->total() }}</span>
        {{ $units->withQueryString()->links() }}
    </div>
</div>

{{-- Modal --}}
<div class="modal-overlay" x-show="showModal" x-cloak @click.self="showModal = false" x-transition>
    <div class="modal modal--sm">
        <div class="modal__header">
            <h3 x-text="editUnit ? 'Modifier l\'unité' : 'Nouvelle unité'"></h3>
            <button class="modal__close" @click="showModal = false">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form :action="editUnit ? '/units/' + editUnit.id : '{{ route('units.store') }}'" method="POST" class="modal__body">
            @csrf
            <template x-if="editUnit"><input type="hidden" name="_method" value="PUT"></template>
            <div class="form-grid form-grid--2 form-grid--gap-16">
                <div class="form-group">
                    <label>Nom <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" :value="editUnit ? editUnit.name : ''" required placeholder="ex: Kilogramme">
                </div>
                <div class="form-group">
                    <label>Abréviation <span class="required">*</span></label>
                    <input type="text" name="abbreviation" class="form-control" :value="editUnit ? editUnit.abbreviation : ''" required placeholder="ex: kg">
                </div>
            </div>
            <div class="modal-footer-inline">
                <button type="button" @click="showModal = false" class="btn btn--ghost">Annuler</button>
                <button type="submit" class="btn btn--primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection
