@extends('layouts.app')
@section('title', $product->display_name)
@section('breadcrumb')
    <a href="{{ route('products.index') }}">Produits</a>
    <span class="sep">/</span>
    <span class="current">{{ $product->display_name }}</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>{{ $product->display_name }}</h2>
        <p>Fiche produit détaillée</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('products.edit', $product) }}" class="btn btn--outline">Modifier</a>
        <a href="{{ route('products.index') }}" class="btn btn--ghost">← Retour</a>
    </div>
</div>

<div class="product-show__layout">
    <div class="product-show__main">
        <div class="card">
            <div class="card__header"><h3>Informations</h3></div>
            <div class="form-grid form-grid--2">
                <div>
                    <p class="field-label">Catégorie</p>
                    <p>{{ $product->category?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="field-label">Marque</p>
                    <p>{{ $product->brand?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="field-label">Unité</p>
                    <p>{{ $product->unit?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="field-label">Code-barres</p>
                    <p>{{ $product->barcode ?? '—' }}</p>
                </div>
                <div>
                    <p class="field-label">Prix d'achat</p>
                    <p>{{ \App\Helpers\FormatHelper::money($product->buying_price) }}</p>
                </div>
                <div>
                    <p class="field-label">Prix de vente</p>
                    <p class="field-price-main">{{ \App\Helpers\FormatHelper::money($product->selling_price) }}</p>
                </div>
                <div>
                    <p class="field-label">Stock actuel</p>
                    <p class="{{ $product->isLowStock() ? 'field-stock--low' : 'field-stock--ok' }}">
                        {{ $product->stock_quantity }} {{ $product->unit?->abbreviation ?? 'u.' }}
                        @if($product->isLowStock()) <span class="badge badge--red">Stock faible</span> @endif
                    </p>
                </div>
                <div>
                    <p class="field-label">Péremption</p>
                    <p>{{ $product->expiry_date ? \App\Helpers\FormatHelper::date($product->expiry_date) : '—' }}</p>
                </div>
            </div>

            @if($product->description)
                <div class="product-show__description">
                    <p class="field-label">Description</p>
                    <p>{{ $product->description }}</p>
                </div>
            @endif
        </div>

        <div class="card">
            <div class="card__header"><h3>Mouvements de stock récents</h3></div>
            <table class="data-table">
                <thead><tr><th>Type</th><th>Qté</th><th>Référence</th><th>Date</th></tr></thead>
                <tbody>
                    @forelse($product->stockMovements as $mv)
                        <tr>
                            <td><span class="badge badge--{{ $mv->quantity > 0 ? 'green' : 'red' }}">{{ $mv->type }}</span></td>
                            <td class="movement-qty {{ $mv->quantity > 0 ? 'movement-qty--positive' : 'movement-qty--negative' }}">
                                {{ $mv->quantity > 0 ? '+' : '' }}{{ $mv->quantity }}
                            </td>
                            <td>{{ $mv->reference ?? '—' }}</td>
                            <td>{{ \App\Helpers\FormatHelper::datetime($mv->created_at) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="table-empty-cell--sm">Aucun mouvement</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="product-show__aside">
        @if($product->getFirstMediaUrl('images'))
            <div class="card">
                <img src="{{ $product->getFirstMediaUrl('images') }}" class="product-show__img">
            </div>
        @endif

        <div class="card">
            <div class="card__header"><h3>Statut</h3></div>
            <div class="status-list">
                <div class="status-row">
                    <span class="status-row__label">Actif</span>
                    @if($product->is_active) <span class="badge badge--green">Oui</span> @else <span class="badge badge--gray">Non</span> @endif
                </div>
                <div class="status-row">
                    <span class="status-row__label">Packable</span>
                    @if($product->can_be_packed) <span class="badge badge--pack">📦 Oui</span> @else <span class="badge badge--gray">Non</span> @endif
                </div>
                <div class="status-row">
                    <span class="status-row__label">Variations</span>
                    @if($product->has_variations) <span class="badge badge--blue">Oui</span> @else <span class="badge badge--gray">Non</span> @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
