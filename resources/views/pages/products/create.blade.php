@extends('layouts.app')
@section('title', 'Ajouter un produit')
@section('breadcrumb')
    <a href="{{ route('products.index') }}">Produits</a>
    <span class="sep">/</span>
    <span class="current">Ajouter</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>Ajouter un produit</h2>
    </div>
    <a href="{{ route('products.index') }}" class="btn btn--ghost">← Retour</a>
</div>

<form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="product-form__layout">

        <div class="product-form__main">
            <div class="card">
                <div class="card__header"><h3>Informations générales</h3></div>
                <div class="form-grid form-grid--2">
                    <div class="form-group form-group--full">
                        <label>Nom du produit <span class="required">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') form-control--error @enderror" value="{{ old('name') }}" required>
                        @error('name') <span class="form-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group form-group--full">
                        <label>Variation</label>
                        <input type="text" name="variation" class="form-control @error('variation') form-control--error @enderror" value="{{ old('variation') }}" placeholder="ex: Rouge - XL">
                        <p class="form-hint">Optionnel — laisser vide si le produit n'a pas de variation. Sera affiché sous la forme « Nom - Variation ».</p>
                        @error('variation') <span class="form-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>Catégorie</label>
                        <select name="category_id" class="form-select">
                            <option value="">— Sélectionner —</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Unité</label>
                        <select name="unit_id" class="form-select">
                            <option value="">— Sélectionner —</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>{{ $unit->name }} ({{ $unit->abbreviation }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group form-group--full">
                        <label>Description</label>
                        <textarea name="description" class="form-textarea" rows="3">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card__header"><h3>Prix & Taxes</h3></div>
                <div class="form-grid form-grid--3">
                    <div class="form-group">
                        <label>Prix d'achat <span class="required">*</span></label>
                        <input type="number" name="buying_price" class="form-control @error('buying_price') form-control--error @enderror" value="{{ old('buying_price', 0) }}" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Prix de vente <span class="required">*</span></label>
                        <input type="number" name="selling_price" class="form-control @error('selling_price') form-control--error @enderror" value="{{ old('selling_price', 0) }}" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Taux TVA (%)</label>
                        <input type="number" name="tax_rate" class="form-control" value="{{ old('tax_rate', 0) }}" step="0.01" min="0" max="100">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card__header"><h3>Stock</h3></div>
                <div class="form-grid form-grid--3">
                    <div class="form-group">
                        <label>Stock initial</label>
                        <input type="number" name="stock_quantity" class="form-control" value="{{ old('stock_quantity', 0) }}" min="0">
                    </div>
                    <div class="form-group">
                        <label>Stock minimum (alerte)</label>
                        <input type="number" name="min_stock_quantity" class="form-control" value="{{ old('min_stock_quantity', 0) }}" min="0">
                    </div>
                    <div class="form-group">
                        <label>Date de péremption</label>
                        <input type="date" name="expiry_date" class="form-control" value="{{ old('expiry_date') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="product-form__aside">
            <div class="card">
                <div class="card__header"><h3>Options</h3></div>
                <div class="product-options">
                    <label class="form-checkbox">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        Produit actif
                    </label>
                    <div x-data="{ packable: {{ old('can_be_packed') ? 'true' : 'false' }} }">
                        <label class="form-checkbox">
                            <input type="checkbox" name="can_be_packed" value="1" x-model="packable" {{ old('can_be_packed') ? 'checked' : '' }}>
                            <div>
                                <strong>Vendu en pack / lot</strong>
                                <p class="pack-option-hint">Activer si ce produit se vend aussi par pack (boîte, carton, lot...).</p>
                            </div>
                        </label>
                        <div x-show="packable" x-cloak class="pack-option-box">
                            <div>
                                <label class="pack-option-label">
                                    Quantité par pack <span class="required">*</span>
                                </label>
                                <input type="number" name="pack_quantity" min="1" step="1"
                                    class="form-control pack-option-input--qty"
                                    value="{{ old('pack_quantity', 1) }}"
                                    placeholder="ex: 6">
                                <p class="pack-option-hint-sm">
                                    Ex : boîte de 6 → saisir <strong>6</strong>. Le stock sera réduit de 6 unités à la vente.
                                </p>
                            </div>
                            <div>
                                <label class="pack-option-label">
                                    Prix du pack ({{ $currency }})
                                </label>
                                <input type="number" name="pack_price" min="0" step="1"
                                    class="form-control pack-option-input--price"
                                    value="{{ old('pack_price') }}"
                                    placeholder="Laisser vide = auto">
                                <p class="pack-option-hint-sm">
                                    Si vide, le prix du pack = prix unitaire × quantité.
                                </p>
                            </div>
                        </div>
                    </div>
                    <label class="form-checkbox">
                        <input type="checkbox" name="has_variations" value="1" {{ old('has_variations') ? 'checked' : '' }}>
                        A des variations (taille, couleur...)
                    </label>
                </div>
            </div>

            <div class="card" x-data="imagePreview()">
                <div class="card__header"><h3>Images</h3></div>
                <div class="image-preview-grid" x-show="previews.length" x-cloak>
                    <template x-for="(src, i) in previews" :key="i">
                        <img :src="src" class="image-preview-grid__img">
                    </template>
                </div>
                <div class="form-group">
                    <label>Photos du produit</label>
                    <input type="file" name="images[]" class="form-control" accept="image/*" multiple @change="onChange($event)">
                    <p class="form-hint">JPEG, PNG, WebP — Max 2 Mo par image</p>
                </div>
            </div>

            <div class="product-form__actions">
                <button type="submit" class="btn btn--primary">Enregistrer</button>
                <a href="{{ route('products.index') }}" class="btn btn--light">Annuler</a>
            </div>
        </div>
    </div>
</form>
@endsection
