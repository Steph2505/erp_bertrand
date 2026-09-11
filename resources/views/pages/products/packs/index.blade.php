@extends('layouts.app')
@section('title', 'Packs')
@section('breadcrumb')
    <a href="{{ route('products.index') }}">Produits</a>
    <span class="sep">/</span>
    <span class="current">Packs</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>Packs</h2>
        <p>{{ $packs->total() }} pack(s) — ventes groupées à déduction automatique en unités</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('packs.create') }}" class="btn btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Ajouter un pack
        </a>
    </div>
</div>

<div class="alert alert--info alert--mb">
    <div class="alert__icon">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
    </div>
    <div class="alert__content">
        <strong>Règle d'or :</strong> Le stock est toujours géré en <strong>unités</strong>. Vendre un pack déduit automatiquement les unités de chaque produit composant.
    </div>
</div>

<div class="table-wrapper">
    <div class="table-wrapper__header">
        <form method="GET" class="filters-bar">
            <div class="input-group">
                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input type="text" name="search" class="form-control" placeholder="Rechercher..." value="{{ request('search') }}">
            </div>
            <select name="is_active" class="form-select">
                <option value="">Tous statuts</option>
                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Actif</option>
                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactif</option>
            </select>
            <button type="submit" class="btn btn--outline">Filtrer</button>
            <a href="{{ route('packs.index') }}" class="btn btn--ghost">Réinitialiser</a>
        </form>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Pack</th>
                <th>Composition</th>
                <th>Prix achat</th>
                <th>Prix vente</th>
                <th>Marge</th>
                <th>Dispo.</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($packs as $pack)
                @php
                    $defPrice  = $pack->defaultPrice;
                    $buy       = $defPrice?->buying_price ?? 0;
                    $sell      = $defPrice?->selling_price ?? 0;
                    $margin    = $buy > 0 ? round((($sell - $buy) / $buy) * 100, 1) : 0;
                    $available = $stockService->availablePackCount($pack->id);
                @endphp
                <tr>
                    <td>
                        <div class="pack-cell">
                            @if($pack->getFirstMediaUrl('images'))
                                <img src="{{ $pack->getFirstMediaUrl('images') }}" class="data-table__img">
                            @else
                                <div class="data-table__img pack-cell__img-placeholder">📦</div>
                            @endif
                            <div>
                                <a href="{{ route('packs.show', $pack) }}" class="pack-cell__name">{{ $pack->name }}</a>
                                @if($pack->barcode)
                                    <div class="pack-cell__barcode">{{ $pack->barcode }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="pack-composition-summary">
                            @foreach($pack->items as $item)
                                <span class="pack-composition-summary__item">{{ $item->quantity }}× {{ $item->product->display_name }}</span>
                            @endforeach
                        </div>
                    </td>
                    <td>{{ \App\Helpers\FormatHelper::money($buy) }}</td>
                    <td><strong>{{ \App\Helpers\FormatHelper::money($sell) }}</strong></td>
                    <td>
                        <span class="margin-value {{ $margin >= 0 ? 'margin-value--positive' : 'margin-value--negative' }}">
                            {{ $margin >= 0 ? '+' : '' }}{{ $margin }} %
                        </span>
                    </td>
                    <td>
                        <strong class="pack-available__count">{{ $available }}</strong>
                        <span class="pack-available__label"> packs</span>
                    </td>
                    <td>
                        @if($pack->is_active)
                            <span class="badge badge--green">Actif</span>
                        @else
                            <span class="badge badge--gray">Inactif</span>
                        @endif
                    </td>
                    <td>
                        <div class="data-table__actions">
                            <a href="{{ route('packs.show', $pack) }}" class="btn btn--ghost btn--icon" title="Voir">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            </a>
                            <a href="{{ route('packs.edit', $pack) }}" class="btn btn--ghost btn--icon" title="Modifier">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                            </a>
                            <form method="POST" action="{{ route('packs.toggle-active', $pack) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn--ghost btn--icon" title="{{ $pack->is_active ? 'Désactiver' : 'Activer' }}">
                                    @if($pack->is_active)
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="btn-toggle--warning"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="btn-toggle--success"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                    @endif
                                </button>
                            </form>
                            <form method="POST" action="{{ route('packs.destroy', $pack) }}" @submit.prevent="window.confirmDialog('Supprimer ce pack ?', {variant:'danger', confirmLabel:'Supprimer'}).then(ok => ok && $el.submit())">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn--ghost btn--icon btn-table-delete" title="Supprimer">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="table-empty-cell">
                        Aucun pack créé.
                        <a href="{{ route('packs.create') }}">Créer le premier pack</a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="table-wrapper__footer">
        <span>{{ $packs->firstItem() ?? 0 }}-{{ $packs->lastItem() ?? 0 }} sur {{ $packs->total() }}</span>
        {{ $packs->withQueryString()->links() }}
    </div>
</div>
@endsection
