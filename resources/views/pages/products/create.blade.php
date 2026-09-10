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
                    <div style="display:contents"
                         x-data="productQuickCreate(
                             {{ Js::from($categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name])) }},
                             {{ Js::from($units->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'abbreviation' => $u->abbreviation])) }},
                             {{ old('category_id') ? (int) old('category_id') : 'null' }},
                             {{ old('unit_id') ? (int) old('unit_id') : 'null' }}
                         )">
                        <div class="form-group">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                                <label style="margin-bottom:0;">Catégorie</label>
                                <button type="button"
                                        @click="showCreateCategory=!showCreateCategory; createCategoryError=''"
                                        style="font-size:12px;color:#1749B3;background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:4px;padding:0;font-weight:600;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:13px;height:13px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    <span x-text="showCreateCategory ? 'Annuler' : 'Nouvelle catégorie'"></span>
                                </button>
                            </div>

                            <select name="category_id" class="form-select" x-model="categoryId" x-show="!showCreateCategory">
                                <option value="">— Sélectionner —</option>
                                <template x-for="cat in categories" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.name"></option>
                                </template>
                            </select>

                            <div x-show="showCreateCategory" x-collapse style="padding:12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;">
                                <div style="display:flex;gap:8px;align-items:flex-end;">
                                    <div class="form-group" style="flex:1;margin-bottom:0;">
                                        <label style="font-size:12px;">Nom de la catégorie</label>
                                        <input type="text" x-model="newCategoryName"
                                               @keydown.enter.prevent="createCategory()"
                                               @keydown.escape="showCreateCategory=false"
                                               class="form-control" placeholder="Ex : Boissons">
                                    </div>
                                    <button type="button" @click="createCategory()"
                                            class="btn btn--primary btn--sm"
                                            :disabled="creatingCategory || !newCategoryName.trim()">
                                        <span x-show="!creatingCategory">Créer</span>
                                        <span x-show="creatingCategory">...</span>
                                    </button>
                                </div>
                                <p x-show="createCategoryError" x-text="createCategoryError" style="color:#ef4444;font-size:12px;margin-top:6px;margin-bottom:0;"></p>
                            </div>
                        </div>

                        <div class="form-group">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                                <label style="margin-bottom:0;">Unité</label>
                                <button type="button"
                                        @click="showCreateUnit=!showCreateUnit; createUnitError=''"
                                        style="font-size:12px;color:#1749B3;background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:4px;padding:0;font-weight:600;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:13px;height:13px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    <span x-text="showCreateUnit ? 'Annuler' : 'Nouvelle unité'"></span>
                                </button>
                            </div>

                            <select name="unit_id" class="form-select" x-model="unitId" x-show="!showCreateUnit">
                                <option value="">— Sélectionner —</option>
                                <template x-for="unit in units" :key="unit.id">
                                    <option :value="unit.id" x-text="unit.name + ' (' + unit.abbreviation + ')'"></option>
                                </template>
                            </select>

                            <div x-show="showCreateUnit" x-collapse style="padding:12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;">
                                <div style="display:flex;gap:8px;align-items:flex-end;">
                                    <div class="form-group" style="flex:1;margin-bottom:0;">
                                        <label style="font-size:12px;">Nom</label>
                                        <input type="text" x-model="newUnitName"
                                               @keydown.enter.prevent="createUnit()"
                                               @keydown.escape="showCreateUnit=false"
                                               class="form-control" placeholder="Ex : Kilogramme">
                                    </div>
                                    <div class="form-group" style="width:90px;margin-bottom:0;">
                                        <label style="font-size:12px;">Abrév.</label>
                                        <input type="text" x-model="newUnitAbbr"
                                               @keydown.enter.prevent="createUnit()"
                                               @keydown.escape="showCreateUnit=false"
                                               class="form-control" placeholder="kg">
                                    </div>
                                    <button type="button" @click="createUnit()"
                                            class="btn btn--primary btn--sm"
                                            :disabled="creatingUnit || !newUnitName.trim() || !newUnitAbbr.trim()">
                                        <span x-show="!creatingUnit">Créer</span>
                                        <span x-show="creatingUnit">...</span>
                                    </button>
                                </div>
                                <p x-show="createUnitError" x-text="createUnitError" style="color:#ef4444;font-size:12px;margin-top:6px;margin-bottom:0;"></p>
                            </div>
                        </div>
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
