@extends('layouts.app')
@section('title', 'Créer un pack')
@section('breadcrumb')
    <a href="{{ route('products.index') }}">Produits</a>
    <span class="sep">/</span>
    <a href="{{ route('packs.index') }}">Packs</a>
    <span class="sep">/</span>
    <span class="current">Créer</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>Créer un pack</h2>
        <p>Regroupez des produits — le stock sera déduit en unités à chaque vente.</p>
    </div>
    <a href="{{ route('packs.index') }}" class="btn btn--ghost">← Retour</a>
</div>

<form method="POST" action="{{ route('packs.store') }}" enctype="multipart/form-data"
      x-data="packForm()" @submit.prevent="submitForm">
    @csrf

    <div class="pack-form__layout">

        <div class="pack-form__main">

            <div class="card">
                <div class="card__header"><h3>Informations générales</h3></div>
                <div class="form-grid form-grid--2">
                    <div class="form-group form-group--full">
                        <label>Nom du pack <span class="required">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') form-control--error @enderror"
                               value="{{ old('name') }}" placeholder="ex : Boîte 6 Croissants" required>
                        @error('name') <span class="form-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Code-barres</label>
                        <input type="text" name="barcode" class="form-control" value="{{ old('barcode') }}" placeholder="EAN13 / Code128">
                    </div>
                    <div class="form-group form-group--full">
                        <label>Description</label>
                        <textarea name="description" class="form-textarea" rows="2">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card__header">
                    <h3>Composition du pack</h3>
                    <span class="composition-hint">Ajoutez les produits et leurs quantités en unités</span>
                </div>

                @error('items') <div class="alert alert--error"><div class="alert__content">{{ $message }}</div></div> @enderror

                <div class="product-autocomplete product-autocomplete--mb" x-data="productSearch()">
                    <div class="input-group">
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                        <input type="text"
                               class="form-control"
                               placeholder="Rechercher un produit packable..."
                               x-model="query"
                               @input.debounce.300ms="search()"
                               @keydown.escape="results = []"
                               autocomplete="off">
                    </div>
                    <div class="product-autocomplete__results" x-show="results.length > 0">
                        <template x-for="p in results" :key="p.id">
                            <div class="product-autocomplete__item" @click="addProduct(p)">
                                <div>
                                    <div class="product-autocomplete__item-name" x-text="p.name"></div>
                                    <div class="product-autocomplete__item-stock" x-text="'Stock : ' + p.stock + ' u.'"></div>
                                </div>
                                <div class="product-autocomplete__item-price" x-text="p.buying_price + ' ' + window.CURRENCY"></div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="pack-composer">
                    <div class="pack-composer__header">
                        <span>Produit</span>
                        <span>Qté (unités)</span>
                        <span>Coût unitaire</span>
                        <span></span>
                    </div>
                    <template x-if="items.length === 0">
                        <div class="pack-composer__empty">Aucun produit ajouté. Recherchez un produit ci-dessus.</div>
                    </template>
                    <template x-for="(item, index) in items" :key="index">
                        <div class="pack-composer__row">
                            <div class="pack-composer__product-name">
                                <span x-text="item.name"></span>
                                <span class="pack-composer__product-name-unit" x-text="item.unit"></span>
                                <input type="hidden" :name="'items[' + index + '][product_id]'" :value="item.id">
                            </div>
                            <div>
                                <input type="number"
                                       class="pack-composer__qty-input"
                                       :name="'items[' + index + '][quantity]'"
                                       x-model.number="item.quantity"
                                       min="1"
                                       @change="updateBuyingPrice()">
                            </div>
                            <div class="pack-composer__unit-cost" x-text="item.buying_price + ' ' + window.CURRENCY + '/u.'"></div>
                            <button type="button" class="pack-composer__remove" @click="removeItem(index)">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                    <div class="pack-composer__footer">
                        <span><strong x-text="items.length"></strong> produit(s) — <strong x-text="totalUnits()"></strong> unités au total</span>
                        <span class="total-cost">Coût total pack : <span x-text="calculatedBuyingPrice.toFixed(0) + ' ' + window.CURRENCY"></span></span>
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
                            <input type="number" name="prices[0][buying_price]" class="form-control"
                                   :value="calculatedBuyingPrice.toFixed(0)"
                                   x-model.number="manualBuyingPrice"
                                   step="0.01" min="0">
                            <input type="hidden" name="prices[0][price_group_id]" value="">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label>Prix de vente</label>
                            <input type="number" name="prices[0][selling_price]" class="form-control"
                                   x-model.number="defaultSellingPrice" step="0.01" min="0">
                        </div>
                        <div class="pack-price-group__margin">
                            <template x-if="manualBuyingPrice > 0">
                                <span :class="margin >= 0 ? 'pack-price-group__margin--positive' : 'pack-price-group__margin--negative'">
                                    <span x-text="(margin >= 0 ? '+' : '') + margin.toFixed(1) + '%'"></span>
                                </span>
                            </template>
                        </div>
                    </div>
                </div>

                @foreach($priceGroups as $i => $group)
                    <div class="pack-price-group">
                        <div class="pack-price-group__label">{{ $group->name }}</div>
                        <div class="pack-price-group__row">
                            <div class="form-group" style="margin:0;">
                                <label>Prix d'achat</label>
                                <input type="number" name="prices[{{ $i + 1 }}][buying_price]" class="form-control" value="0" step="0.01" min="0">
                                <input type="hidden" name="prices[{{ $i + 1 }}][price_group_id]" value="{{ $group->id }}">
                            </div>
                            <div class="form-group" style="margin:0;">
                                <label>Prix de vente</label>
                                <input type="number" name="prices[{{ $i + 1 }}][selling_price]" class="form-control" value="0" step="0.01" min="0">
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
                    <input type="checkbox" name="is_active" value="1" checked>
                    Pack actif (visible à la vente)
                </label>
            </div>

            <div class="card">
                <div class="card__header"><h3>Image du pack</h3></div>
                <div class="form-group">
                    <label>Photo</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    <p class="form-hint">JPEG, PNG — Max 2 Mo</p>
                </div>
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
                <button type="submit" class="btn btn--primary">Créer le pack</button>
                <a href="{{ route('packs.index') }}" class="btn btn--light">Annuler</a>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
function packForm() {
    return {
        items: [],
        manualBuyingPrice: 0,
        defaultSellingPrice: 0,
        get calculatedBuyingPrice() {
            return this.items.reduce((sum, item) => sum + (item.buying_price * item.quantity), 0);
        },
        get margin() {
            if (this.manualBuyingPrice <= 0) return 0;
            return ((this.defaultSellingPrice - this.manualBuyingPrice) / this.manualBuyingPrice) * 100;
        },
        totalUnits() {
            return this.items.reduce((sum, item) => sum + item.quantity, 0);
        },
        addProduct(product) {
            const exists = this.items.find(i => i.id === product.id);
            if (exists) { exists.quantity++; } else {
                this.items.push({ id: product.id, name: product.name, buying_price: product.buying_price, stock: product.stock, quantity: 1, unit: '' });
            }
            this.updateBuyingPrice();
        },
        removeItem(index) {
            this.items.splice(index, 1);
            this.updateBuyingPrice();
        },
        updateBuyingPrice() {
            this.manualBuyingPrice = this.calculatedBuyingPrice;
        },
        submitForm() {
            this.$el.submit();
        }
    }
}

function productSearch() {
    return {
        query: '',
        results: [],
        async search() {
            if (this.query.length < 2) { this.results = []; return; }
            const res = await fetch(`/packs/search-products?q=${encodeURIComponent(this.query)}`);
            this.results = await res.json();
        },
        addProduct(p) {
            this.query = '';
            this.results = [];
            Alpine.$data(document.querySelector('[x-data="packForm()"]')).addProduct(p);
        }
    }
}
</script>
@endpush
