@extends('layouts.app')
@section('title', $pack->name)
@section('breadcrumb')
    <a href="{{ route('products.index') }}">Produits</a>
    <span class="sep">/</span>
    <a href="{{ route('packs.index') }}">Packs</a>
    <span class="sep">/</span>
    <span class="current">{{ $pack->name }}</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>{{ $pack->name }}</h2>
        <p>Détail du pack — composition et disponibilité</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('packs.edit', $pack) }}" class="btn btn--outline">Modifier</a>
        <a href="{{ route('packs.index') }}" class="btn btn--ghost">← Retour</a>
    </div>
</div>

<div class="pack-show__layout">

    <div class="pack-show__main">
        <div class="card">
            <div class="card__header">
                <h3>Composition du pack</h3>
                <span class="badge badge--pack">{{ $pack->items->count() }} produit(s)</span>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Unités incluses</th>
                        <th>Stock actuel</th>
                        <th>Packs faisables</th>
                        <th>Coût portion</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pack->items as $item)
                        <tr>
                            <td>
                                <a href="{{ route('products.show', $item->product) }}" class="font-600">{{ $item->product->display_name }}</a>
                                <div class="text-xs text-muted">{{ $item->product->unit?->abbreviation ?? 'u.' }}</div>
                            </td>
                            <td><strong>{{ $item->quantity }}</strong> u.</td>
                            <td class="{{ $item->product->isLowStock() ? 'pack-stock-cell--low' : '' }}">
                                {{ $item->product->stock_quantity }} {{ $item->product->unit?->abbreviation ?? 'u.' }}
                            </td>
                            <td>
                                @php $faisable = floor($item->product->stock_quantity / $item->quantity); @endphp
                                <strong>{{ $faisable }}</strong> packs
                            </td>
                            <td>{{ \App\Helpers\FormatHelper::money($item->product->buying_price * $item->quantity) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="pack-availability {{ $availableCount > 0 ? 'pack-availability--ok' : 'pack-availability--empty' }}">
                <div class="pack-availability__inner">
                    <div>
                        <p class="pack-availability__label">Packs complets disponibles avec le stock actuel</p>
                        <p class="pack-availability__count {{ $availableCount > 0 ? 'pack-availability__count--ok' : 'pack-availability__count--empty' }}">
                            {{ $availableCount }} pack(s)
                        </p>
                    </div>
                    <div class="pack-availability__icon">{{ $availableCount > 0 ? '📦' : '⚠️' }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card__header"><h3>Historique des ventes de ce pack</h3></div>
            <table class="data-table">
                <thead>
                    <tr><th>Produit impacté</th><th>Mouvement</th><th>Référence</th><th>Date</th></tr>
                </thead>
                <tbody>
                    @php
                        $movements = \App\Models\StockMovement::where('pack_id', $pack->id)->with('product')->latest()->limit(20)->get();
                    @endphp
                    @forelse($movements as $mv)
                        <tr>
                            <td>{{ $mv->product->display_name }}</td>
                            <td class="mv-qty {{ $mv->quantity > 0 ? 'mv-qty--positive' : 'mv-qty--negative' }}">
                                {{ $mv->quantity > 0 ? '+' : '' }}{{ $mv->quantity }} u.
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

    <div class="pack-show__aside">
        @if($pack->getFirstMediaUrl('images'))
            <div class="card pack-show__img-card">
                <img src="{{ $pack->getFirstMediaUrl('images') }}" class="pack-show__img">
            </div>
        @endif

        <div class="card">
            <div class="card__header"><h3>Statut</h3></div>
            <div class="pack-status-row">
                <span class="pack-status-row__label">Statut</span>
                @if($pack->is_active) <span class="badge badge--green">Actif</span> @else <span class="badge badge--gray">Inactif</span> @endif
            </div>
            @if($pack->description)
                <p class="pack-description">{{ $pack->description }}</p>
            @endif
        </div>

        <div class="card">
            <div class="card__header"><h3>Tarification</h3></div>
            @forelse($pack->prices as $price)
                <div class="price-row">
                    <div>
                        <p class="price-row__group-name">{{ $price->priceGroup?->name ?? 'Prix par défaut' }}</p>
                        <p class="price-row__buy">Achat : {{ \App\Helpers\FormatHelper::money($price->buying_price) }}</p>
                    </div>
                    <div>
                        <p class="price-row__sell">{{ \App\Helpers\FormatHelper::money($price->selling_price) }}</p>
                        <p class="price-row__margin {{ $price->margin_percent >= 0 ? 'price-row__margin--positive' : 'price-row__margin--negative' }}">
                            {{ $price->margin_percent >= 0 ? '+' : '' }}{{ $price->margin_percent }} %
                        </p>
                    </div>
                </div>
            @empty
                <p class="price-empty">Aucun prix configuré.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
