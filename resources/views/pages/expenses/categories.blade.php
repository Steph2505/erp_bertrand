@extends('layouts.app')
@section('title', 'Catégories de dépenses')
@section('breadcrumb')
    <a href="{{ route('expenses.index') }}">Dépenses</a>
    <span class="sep">/</span><span class="current">Catégories</span>
@endsection

@section('content')
<div x-data="{ showModal: false, editCat: null }">

<div class="page-header">
    <div class="page-header__title">
        <h2>Catégories de dépenses</h2>
        <p>{{ $categories->total() }} catégorie(s)</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('expenses.index') }}" class="btn btn--ghost">← Dépenses</a>
        <button @click="showModal = true; editCat = null" class="btn btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nouvelle catégorie
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert--success" style="margin-bottom:12px;">{{ session('success') }}</div>
@endif
@if($errors->has('category'))
<div class="alert alert--danger" style="margin-bottom:12px;">{{ $errors->first('category') }}</div>
@endif

<div class="table-wrapper">
    <table class="data-table">
        <thead><tr>
            <th>Nom</th>
            <th style="text-align:center">Dépenses associées</th>
            <th style="text-align:right">Actions</th>
        </tr></thead>
        <tbody>
            @forelse($categories as $cat)
            <tr>
                <td style="font-weight:500;">{{ $cat->name }}</td>
                <td style="text-align:center;">
                    <span class="badge badge--gray">{{ $cat->expenses_count }}</span>
                </td>
                <td>
                    <div class="data-table__actions" style="justify-content:flex-end;">
                        <button @click="editCat = { id: {{ $cat->id }}, name: '{{ addslashes($cat->name) }}' }; showModal = true"
                                class="btn btn--ghost btn--sm btn--icon" title="Modifier">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                        </button>
                        @if($cat->expenses_count === 0)
                        <form method="POST" action="{{ route('expenses.categories.destroy', $cat) }}"
                              onsubmit="return confirm('Supprimer cette catégorie ?')" style="display:inline;">
                            @csrf @method('DELETE')
                            <button class="btn btn--danger btn--sm btn--icon" title="Supprimer">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="3" style="text-align:center;padding:40px;color:#64748B;">Aucune catégorie</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="table-wrapper__footer">
        <span>{{ $categories->firstItem() ?? 0 }}–{{ $categories->lastItem() ?? 0 }} sur {{ $categories->total() }}</span>
        {{ $categories->links() }}
    </div>
</div>

{{-- Modal créer / modifier --}}
<div class="modal-overlay" x-show="showModal" x-cloak x-transition @click.self="showModal=false">
    <div class="modal modal--sm">
        <div class="modal__header">
            <h3 x-text="editCat ? 'Modifier la catégorie' : 'Nouvelle catégorie'"></h3>
            <button @click="showModal=false" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:20px;line-height:1;">×</button>
        </div>
        <form :action="editCat ? '/expenses/categories/'+editCat.id : '{{ route('expenses.categories.store') }}'"
              method="POST" class="modal__body">
            @csrf
            <template x-if="editCat"><input type="hidden" name="_method" value="PUT"></template>
            <div class="form-group">
                <label>Nom <span class="required">*</span></label>
                <input type="text" name="name" class="form-control" :value="editCat ? editCat.name : ''"
                       placeholder="Ex : Loyer, Carburant..." required autofocus>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">
                <button type="button" @click="showModal=false" class="btn btn--ghost">Annuler</button>
                <button type="submit" class="btn btn--primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection
