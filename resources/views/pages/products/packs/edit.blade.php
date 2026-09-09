@extends('layouts.app')
@section('title', 'Modifier : ' . $pack->name)
@section('breadcrumb')
    <a href="{{ route('products.index') }}">Produits</a>
    <span class="sep">/</span>
    <a href="{{ route('packs.index') }}">Packs</a>
    <span class="sep">/</span>
    <span class="current">Modifier</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>Modifier : {{ $pack->name }}</h2>
    </div>
    <a href="{{ route('packs.show', $pack) }}" class="btn btn--ghost">← Détail</a>
</div>

@php
    $existingItems = $pack->items->map(fn($i) => [
        'id'           => $i->product_id,
        'name'         => $i->product->display_name,
        'buying_price' => (float) $i->product->buying_price,
        'stock'        => $i->product->stock_quantity,
        'quantity'     => $i->quantity,
    ])->values()->toArray();

    $defPrice = $pack->defaultPrice;
@endphp

<form method="POST" action="{{ route('packs.update', $pack) }}" enctype="multipart/form-data"
      x-data="packForm({{ json_encode($existingItems) }})">
    @csrf @method('PUT')

    <div class="pack-form__layout">

        <div class="pack-form__main">

            <div class="card">
                <div class="card__header"><h3>Informations générales</h3></div>
                <div class="form-grid form-grid--2">
                    <div class="form-group form-group--full">
                        <label>Nom du pack <span class="required">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $pack->name) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Code-barres</label>
                        <input type="text" name="barcode" class="form-control" value="{{ old('barcode', $pack->barcode) }}">
                    </div>
                    <div class="form-group form-group--full">
                        <label>Description</label>
                        <textarea name="description" class="form-textarea" rows="2">{{ old('description', $pack->description) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card__header"><h3>Composition</h3></div>

                <div class="product-autocomplete product-autocomplete--mb" x-data="productSearch()">
                    <div class="input-group">
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                        <input type="text" class="form-control" placeholder="Ajouter un produit packable..." x-model="query" @input.debounce.300ms="search()" @keydown.escape="results = []" autocomplete="off">
                    </div>
                    <div class="product-autocomplete__results" x-show="results.length > 0">
                        <template x-for="p in results" :key="p.id">
                            <div class="product-autocomplete__item" @click="addProduct(p)">
                                <div class="product-autocomplete__item-name" x-text="p.name"></div>
                                <div class="product-autocomplete__item-price" x-text="p.buying_price + ' ' + window.CURRENCY"></div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="pack-composer">
                    <div class="pack-composer__header">
                        <span>Produit</span><span>Qté (unités)</span><span>Coût unitaire</span><span></span>
                    </div>
                    <template x-if="items.length === 0">
                        <div class="pack-composer__empty">Ajoutez des produits ci-dessus.</div>
                    </template>
                    <template x-for="(item, index) in items" :key="index">
                        <div class="pack-composer__row">
                            <div class="pack-composer__product-name">
                                <span x-text="item.name"></span>
                                <input type="hidden" :name="'items[' + index + '][product_id]'" :value="item.id">
                            </div>
                            <div>
                                <input type="number" class="pack-composer__qty-input" :name="'items[' + index + '][quantity]'" x-model.number="item.quantity" min="1" @change="updateBuyingPrice()">
                            </div>
                            <div class="pack-composer__unit-cost" x-text="item.buying_price + ' ' + window.CURRENCY + '/u.'"></div>
                            <button type="button" class="pack-composer__remove" @click="removeItem(index)">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                    <div class="pack-composer__footer">
                        <span><strong x-text="items.length"></strong> produit(s)</span>
                        <span class="total-cost">Coût : <span x-text="calculatedBuyingPrice.toFixed(0) + ' ' + window.CURRENCY"></span></span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card__header"><h3>Prix du pack</h3></div>
                <div class="pack-price-group">
                    <div class="pack-price-group__label">Prix par défaut</div>
                    <div class="pack-price-group__row">
                        <div class="form-group" style="margin:0;">
                            <label>Prix d'achat</label>
                            <input type="number" name="prices[0][buying_price]" class="form-control" value="{{ old('prices.0.buying_price', $defPrice?->buying_price ?? 0) }}" step="0.01" min="0" x-model.number="manualBuyingPrice">
                            <input type="hidden" name="prices[0][price_group_id]" value="">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label>Prix de vente</label>
                            <input type="number" name="prices[0][selling_price]" class="form-control" value="{{ old('prices.0.selling_price', $defPrice?->selling_price ?? 0) }}" step="0.01" min="0" x-model.number="defaultSellingPrice">
                        </div>
                        <div class="pack-price-group__margin">
                            <span :class="margin >= 0 ? 'pack-price-group__margin--positive' : 'pack-price-group__margin--negative'" x-text="(margin >= 0 ? '+' : '') + margin.toFixed(1) + '%'"></span>
                        </div>
                    </div>
                </div>

                @foreach($priceGroups as $i => $group)
                    @php $gPrice = $pack->prices->firstWhere('price_group_id', $group->id); @endphp
                    <div class="pack-price-group">
                        <div class="pack-price-group__label">{{ $group->name }}</div>
                        <div class="pack-price-group__row">
                            <div class="form-group" style="margin:0;">
                                <label>Prix d'achat</label>
                                <input type="number" name="prices[{{ $i + 1 }}][buying_price]" class="form-control" value="{{ old('prices.' . ($i+1) . '.buying_price', $gPrice?->buying_price ?? 0) }}" step="0.01">
                                <input type="hidden" name="prices[{{ $i + 1 }}][price_group_id]" value="{{ $group->id }}">
                            </div>
                            <div class="form-group" style="margin:0;">
                                <label>Prix de vente</label>
                                <input type="number" name="prices[{{ $i + 1 }}][selling_price]" class="form-control" value="{{ old('prices.' . ($i+1) . '.selling_price', $gPrice?->selling_price ?? 0) }}" step="0.01">
                            </div>
                            <div></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="pack-form__aside">
            <div class="card">
                <div class="card__header"><h3>Options</h3></div>
                <label class="form-checkbox">
                    <input type="checkbox" name="is_active" value="1" {{ $pack->is_active ? 'checked' : '' }}>
                    Pack actif
                </label>
            </div>

            <div class="card">
                <div class="card__header"><h3>Image</h3></div>
                @if($pack->getFirstMediaUrl('images'))
                    <img src="{{ $pack->getFirstMediaUrl('images') }}" class="product-edit__img">
                @endif
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>

            <div class="card pack-summary-card">
                <div class="card__header"><h3>📦 Résumé</h3></div>
                <div class="pack-summary">
                    <div class="pack-summary__row">
                        <span class="pack-summary__label">Produits différents</span>
                        <strong x-text="items.length"></strong>
                    </div>
                    <div class="pack-summary__row">
                        <span class="pack-summary__label">Total unités/pack</span>
                        <strong x-text="totalUnits()"></strong>
                    </div>
                    <div class="pack-summary__divider">
                        <span class="pack-summary__label">Coût de revient</span>
                        <strong class="pack-summary__total-value" x-text="calculatedBuyingPrice.toFixed(0) + ' ' + window.CURRENCY"></strong>
                    </div>
                </div>
            </div>

            <div class="pack-form__actions">
                <button type="submit" class="btn btn--primary">Mettre à jour</button>
                <a href="{{ route('packs.show', $pack) }}" class="btn btn--light">Annuler</a>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
function packForm(initialItems = []) {
    return {
        items: initialItems,
        manualBuyingPrice: {{ $defPrice?->buying_price ?? 0 }},
        defaultSellingPrice: {{ $defPrice?->selling_price ?? 0 }},
        get calculatedBuyingPrice() {
            return this.items.reduce((s, i) => s + i.buying_price * i.quantity, 0);
        },
        get margin() {
            if (!this.manualBuyingPrice) return 0;
            return ((this.defaultSellingPrice - this.manualBuyingPrice) / this.manualBuyingPrice) * 100;
        },
        totalUnits() { return this.items.reduce((s, i) => s + i.quantity, 0); },
        addProduct(p) {
            const ex = this.items.find(i => i.id === p.id);
            if (ex) ex.quantity++; else this.items.push({ id: p.id, name: p.name, buying_price: p.buying_price, quantity: 1 });
            this.manualBuyingPrice = this.calculatedBuyingPrice;
        },
        removeItem(idx) { this.items.splice(idx, 1); this.manualBuyingPrice = this.calculatedBuyingPrice; },
        updateBuyingPrice() { this.manualBuyingPrice = this.calculatedBuyingPrice; }
    }
}
function productSearch() {
    return {
        query: '', results: [],
        async search() {
            if (this.query.length < 2) { this.results = []; return; }
            const r = await fetch(`/packs/search-products?q=${encodeURIComponent(this.query)}`);
            this.results = await r.json();
        },
        addProduct(p) {
            this.query = ''; this.results = [];
            const form = document.querySelector('[x-data^="packForm"]');
            Alpine.$data(form).addProduct(p);
        }
    }
}
</script>
@endpush
