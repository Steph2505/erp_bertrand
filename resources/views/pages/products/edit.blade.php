@extends('layouts.app')
@section('title', 'Modifier : ' . $product->display_name)
@section('breadcrumb')
    <a href="{{ route('products.index') }}">Produits</a>
    <span class="sep">/</span>
    <span class="current">Modifier</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>Modifier : {{ $product->display_name }}</h2>
    </div>
    <a href="{{ route('products.show', $product) }}" class="btn btn--ghost">← Détail</a>
</div>

<form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    <div class="product-form__layout">

        <div class="product-form__main">
            <div class="card">
                <div class="card__header"><h3>Informations générales</h3></div>
                <div class="form-grid form-grid--2">
                    <div class="form-group form-group--full">
                        <label>Nom du produit <span class="required">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                    </div>
                    <div class="form-group form-group--full">
                        <label>Variation</label>
                        <input type="text" name="variation" class="form-control @error('variation') form-control--error @enderror" value="{{ old('variation', $product->variation) }}" placeholder="ex: Rouge - XL">
                        <p class="form-hint">Optionnel — laisser vide si le produit n'a pas de variation. Sera affiché sous la forme « Nom - Variation ».</p>
                        @error('variation') <span class="form-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Catégorie</label>
                        <select name="category_id" class="form-select">
                            <option value="">— Sélectionner —</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Unité</label>
                        <select name="unit_id" class="form-select">
                            <option value="">— Sélectionner —</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" {{ old('unit_id', $product->unit_id) == $unit->id ? 'selected' : '' }}>{{ $unit->name }} ({{ $unit->abbreviation }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form-group--full">
                        <label>Description</label>
                        <textarea name="description" class="form-textarea" rows="3">{{ old('description', $product->description) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card__header"><h3>Prix & Taxes</h3></div>
                <div class="form-grid form-grid--3">
                    <div class="form-group">
                        <label>Prix d'achat <span class="required">*</span></label>
                        <input type="number" name="buying_price" class="form-control" value="{{ old('buying_price', $product->buying_price) }}" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Prix de vente <span class="required">*</span></label>
                        <input type="number" name="selling_price" class="form-control" value="{{ old('selling_price', $product->selling_price) }}" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Taux TVA (%)</label>
                        <input type="number" name="tax_rate" class="form-control" value="{{ old('tax_rate', $product->tax_rate) }}" step="0.01" min="0" max="100">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card__header"><h3>Stock</h3></div>
                <div class="form-grid form-grid--3">
                    <div class="form-group">
                        <label>Stock actuel</label>
                        <input type="number" name="stock_quantity" class="form-control" value="{{ old('stock_quantity', $product->stock_quantity) }}" min="0">
                    </div>
                    <div class="form-group">
                        <label>Stock minimum</label>
                        <input type="number" name="min_stock_quantity" class="form-control" value="{{ old('min_stock_quantity', $product->min_stock_quantity) }}" min="0">
                    </div>
                    <div class="form-group">
                        <label>Date de péremption</label>
                        <input type="date" name="expiry_date" class="form-control" value="{{ old('expiry_date', $product->expiry_date?->format('Y-m-d')) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="product-form__aside">
            <div class="card">
                <div class="card__header"><h3>Options</h3></div>
                <div class="product-options">
                    <label class="form-checkbox">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                        Produit actif
                    </label>
                    <div x-data="{ packable: {{ old('can_be_packed', $product->can_be_packed) ? 'true' : 'false' }} }">
                        <label class="form-checkbox">
                            <input type="checkbox" name="can_be_packed" value="1" x-model="packable" {{ old('can_be_packed', $product->can_be_packed) ? 'checked' : '' }}>
                            <div>
                                <strong>Vendu en pack / lot</strong>
                                <p class="pack-option-hint">Activer si ce produit se vend aussi par pack.</p>
                            </div>
                        </label>
                        <div x-show="packable" x-cloak class="pack-option-box">
                            <div>
                                <label class="pack-option-label">
                                    Quantité par pack <span class="required">*</span>
                                </label>
                                <input type="number" name="pack_quantity" min="1" step="1"
                                    class="form-control pack-option-input--qty"
                                    value="{{ old('pack_quantity', $product->pack_quantity ?? 1) }}"
                                    placeholder="ex: 6">
                                <p class="pack-option-hint-sm">
                                    Ex : boîte de 6 → saisir <strong>6</strong>. Vente "au pack" déduira 6 unités du stock.
                                </p>
                            </div>
                            <div>
                                <label class="pack-option-label">
                                    Prix du pack ({{ $currency }})
                                </label>
                                <input type="number" name="pack_price" min="0" step="1"
                                    class="form-control pack-option-input--price"
                                    value="{{ old('pack_price', $product->pack_price) }}"
                                    placeholder="Laisser vide = auto">
                                <p class="pack-option-hint-sm">
                                    Si vide, le prix du pack = prix unitaire × quantité.
                                </p>
                            </div>
                        </div>
                    </div>
                    <label class="form-checkbox">
                        <input type="checkbox" name="has_variations" value="1" {{ old('has_variations', $product->has_variations) ? 'checked' : '' }}>
                        A des variations
                    </label>
                </div>
            </div>

            <div class="card" x-data="imagePreview()">
                <div class="card__header"><h3>Images</h3></div>
                <template x-if="!previews.length">
                    @if($product->getFirstMediaUrl('images'))
                        <img src="{{ $product->getFirstMediaUrl('images') }}" class="product-edit__img">
                    @endif
                </template>
                <div class="image-preview-grid" x-show="previews.length" x-cloak>
                    <template x-for="(src, i) in previews" :key="i">
                        <img :src="src" class="image-preview-grid__img">
                    </template>
                </div>
                <div class="form-group">
                    <label>Ajouter / remplacer des images</label>
                    <input type="file" name="images[]" class="form-control" accept="image/*" multiple @change="onChange($event)">
                    <p class="form-hint">Les nouvelles images remplaceront l'aperçu ci-dessus après enregistrement</p>
                </div>
            </div>

            <div class="product-form__actions">
                <button type="submit" class="btn btn--primary">Mettre à jour</button>
                <a href="{{ route('products.show', $product) }}" class="btn btn--light">Annuler</a>
            </div>
        </div>
    </div>
</form>
@endsection
