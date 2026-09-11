@extends('layouts.app')
@section('title', 'Catégories produits')
@section('breadcrumb')
<a href="{{ route('products.index') }}">Produits</a>
<span class="current">Catégories</span>
@endsection

@section('content')
<div x-data="{ showModal: false, editCat: null, editActive: true }">

<div class="page-header">
    <div class="page-header__title">
        <h2>Catégories de produits</h2>
        <p>{{ $categories->total() }} catégorie(s)</p>
    </div>
    <div class="page-header__actions">
        <button @click="showModal = true; editCat = null; editActive = true" class="btn btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nouvelle catégorie
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
            <th>Catégorie parent</th>
            <th>Nb produits</th>
            <th>Statut</th>
            <th class="th-right">Actions</th>
        </tr></thead>
        <tbody>
            @forelse($categories as $cat)
            <tr>
                <td>
                    <div class="cat-cell__name">{{ $cat->name }}</div>
                    <div class="cat-cell__slug">{{ $cat->slug }}</div>
                </td>
                <td class="table-cell--muted-sm">{{ $cat->parent?->name ?? '—' }}</td>
                <td class="table-cell--muted">{{ $cat->products_count }}</td>
                <td>
                    <span class="badge badge--{{ $cat->is_active ? 'green' : 'gray' }}">
                        {{ $cat->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td>
                    <div class="data-table__actions">
                        <button @click="editCat = {{ $cat->toJson() }}; editActive = {{ $cat->is_active ? 'true' : 'false' }}; showModal = true"
                            class="btn btn--ghost btn--sm btn--icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                        </button>
                        @if($cat->products_count === 0)
                        <form method="POST" action="{{ route('categories.destroy', $cat) }}" @submit.prevent="window.confirmDialog('Supprimer cette catégorie ?', {variant:'danger', confirmLabel:'Supprimer'}).then(ok => ok && $el.submit())">
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
            <tr><td colspan="5" class="table-empty-cell--sm">Aucune catégorie.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="table-wrapper__footer">
        <span>{{ $categories->firstItem() ?? 0 }}–{{ $categories->lastItem() ?? 0 }} sur {{ $categories->total() }}</span>
        {{ $categories->withQueryString()->links() }}
    </div>
</div>

{{-- Modal --}}
<div class="modal-overlay" x-show="showModal" x-cloak @click.self="showModal = false" x-transition>
    <div class="modal modal--md">
        <div class="modal__header">
            <h3 x-text="editCat ? 'Modifier la catégorie' : 'Nouvelle catégorie'"></h3>
            <button class="modal__close" @click="showModal = false">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form :action="editCat ? '/categories/' + editCat.id : '{{ route('categories.store') }}'" method="POST" class="modal__body">
            @csrf
            <template x-if="editCat"><input type="hidden" name="_method" value="PUT"></template>
            <div class="form-grid form-grid--2 form-grid--gap-16">
                <div class="form-group">
                    <label>Nom <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" :value="editCat ? editCat.name : ''" required placeholder="ex: Viennoiseries">
                </div>
                <div class="form-group">
                    <label>Catégorie parent</label>
                    <select name="parent_id" class="form-select">
                        <option value="">— Aucune (racine) —</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" :selected="editCat && editCat.parent_id == {{ $parent->id }}">{{ $parent->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-checkbox">
                    <input type="checkbox" name="is_active" value="1" x-model="editActive">
                    Catégorie active (visible dans les filtres et formulaires)
                </label>
            </div>
            <div class="modal-footer-inline--sm">
                <button type="button" @click="showModal = false" class="btn btn--ghost">Annuler</button>
                <button type="submit" class="btn btn--primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection
