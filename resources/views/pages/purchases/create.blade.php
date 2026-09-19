@extends('layouts.app')
@section('title', 'Nouvel achat')
@section('breadcrumb')
    <a href="{{ route('purchases.index') }}">Achats</a>
    <span class="sep">/</span><span class="current">Nouvel achat</span>
@endsection

@section('content')
<div x-data="purchaseForm()">
<form method="POST" action="{{ route('purchases.store') }}" id="purchase-form">
@csrf

<div class="page-header">
    <div class="page-header__title"><h2>Nouvel achat</h2></div>
    <div class="page-header__actions">
        <a href="{{ route('purchases.index') }}" class="btn btn--light">Annuler</a>
        <button type="button" @click="submitForm('pending')" class="btn btn--light" :disabled="items.length === 0">
            Brouillon
        </button>
        <button type="button" @click="submitForm('confirmed')" class="btn btn--ghost btn--confirm" :disabled="items.length === 0">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            Confirmer
        </button>
        <button type="button" @click="submitForm('paid')" class="btn btn--primary" :disabled="items.length === 0">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            Payé
        </button>
    </div>
</div>

<div class="purchase-layout purchase-layout--split">
    <div class="purchase-layout__form">

        <div class="card purchase-card">
            <div class="form-grid form-grid--3">

                {{-- Fournisseur --}}
                <div class="form-group" @click.outside="supplierOpen=false; supplierSearch=selectedSupplier?.name??''">
                    <label>Fournisseur <span class="required">*</span></label>
                    <div class="autocomplete-wrap">
                        <input type="text" x-model="supplierSearch"
                               @focus="supplierSearch=''; supplierOpen=true" @input="supplierOpen=true"
                               placeholder="Rechercher un fournisseur..."
                               class="form-control" autocomplete="off">
                        <input type="hidden" name="supplier_id" :value="selectedSupplier?.id??''">
                        <div x-show="supplierOpen" x-transition class="autocomplete-dropdown">
                            <template x-if="filteredSuppliers.length === 0">
                                <div class="autocomplete-dropdown__empty">Aucun résultat</div>
                            </template>
                            <template x-for="s in filteredSuppliers" :key="s.id">
                                <div @mousedown.prevent="selectSupplier(s)"
                                     class="autocomplete-dropdown__item"
                                     :style="selectedSupplier?.id===s.id?'background:#eff6ff;color:#3b82f6;font-weight:600':''"
                                     @mouseover="$el.style.background='#f8fafc'"
                                     @mouseout="$el.style.background=selectedSupplier?.id===s.id?'#eff6ff':'white'">
                                    <span x-text="s.name"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Date <span class="required">*</span></label>
                    <input type="date" name="purchase_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>

                {{-- Entrepôt --}}
                <div class="form-group" @click.outside="warehouseOpen=false; warehouseSearch=selectedWarehouse?.name??''">
                    <label>Entrepôt</label>
                    <div class="autocomplete-wrap">
                        <input type="text" x-model="warehouseSearch"
                               @focus="warehouseSearch=''; warehouseOpen=true" @input="warehouseOpen=true"
                               placeholder="Rechercher un entrepôt..."
                               class="form-control" autocomplete="off">
                        <input type="hidden" name="warehouse_id" :value="selectedWarehouse?.id??''">
                        <div x-show="warehouseOpen" x-transition class="autocomplete-dropdown">
                            <template x-if="filteredWarehouses.length === 0">
                                <div class="autocomplete-dropdown__empty">Aucun résultat</div>
                            </template>
                            <template x-for="w in filteredWarehouses" :key="w.id">
                                <div @mousedown.prevent="selectWarehouse(w)"
                                     class="autocomplete-dropdown__item"
                                     :style="selectedWarehouse?.id===w.id?'background:#eff6ff;color:#3b82f6;font-weight:600':''"
                                     @mouseover="$el.style.background='#f8fafc'"
                                     @mouseout="$el.style.background=selectedWarehouse?.id===w.id?'#eff6ff':'white'">
                                    <span x-text="w.name"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <div class="purchase-layout__recap">
        <div class="card purchase-card">
            <div class="purchase-summary__rows">
                <div class="purchase-summary__row--total">
                    <span>Total</span>
                    <span class="purchase-summary__total-value" x-text="formatMoney(subtotal)"></span>
                </div>
            </div>
            <div class="form-group purchase-summary__account">
                <label>Compte de paiement</label>
                <select name="payment_account_id" x-model.number="paymentAccountId" class="form-select">
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" @click="submitForm()" class="btn btn--primary purchase-summary__cta" :disabled="items.length === 0">
                Enregistrer l'achat
            </button>
        </div>
        <input type="hidden" id="payment-type-input" name="payment_type" value="pending">
    </div>

    <div class="purchase-layout__items">
        <div class="table-wrapper purchase-items">
            <div class="table-wrapper__header">
                <strong>Articles de l'achat</strong>
                <span x-text="items.length + ' article(s)'" class="purchase-items__count"></span>
                <button type="button" @click="addRow()" class="btn btn--primary btn--sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="purchase-items__add-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Ajouter une ligne
                </button>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Article</th>
                        <th class="purchase-items__col-qty">Qté</th>
                        <th class="purchase-items__col-price">Prix unit. ({{ $currency }})</th>
                        <th class="purchase-items__col-sale-price">PU Détail</th>
                        <th class="purchase-items__col-sale-price">PU Gros</th>
                        <th class="purchase-items__col-total">Total</th>
                        <th class="purchase-items__col-action"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="items.length === 0">
                        <tr>
                            <td colspan="7" class="purchase-items__empty-row">
                                Cliquez "Ajouter une ligne" pour commencer
                            </td>
                        </tr>
                    </template>
                    <template x-for="(item, idx) in items" :key="item._key">
                        <tr>
                            <td class="purchase-items__col-product">
                                <div class="purchase-items__product-row">
                                    <div class="autocomplete-wrap purchase-items__product-search" @click.outside="item._open=false">
                                        <input type="text"
                                               x-model="item._search"
                                               @focus="item._open=true"
                                               @input="item._open=true"
                                               :placeholder="item.item_name || 'Rechercher un article...'"
                                               class="form-control purchase-items__product-input" autocomplete="off">
                                        <input type="hidden" :name="'items['+idx+'][item_type]'" value="product">
                                        <input type="hidden" :name="'items['+idx+'][pack_id]'" value="">
                                        <input type="hidden" :name="'items['+idx+'][product_id]'" :value="item.product_id ?? ''">
                                        <input type="hidden" :name="'items['+idx+'][item_name]'" :value="item.item_name">
                                        <div x-show="item._open" x-transition class="autocomplete-dropdown autocomplete-dropdown--wide">
                                            <template x-if="availableForRow(idx).length === 0">
                                                <div class="autocomplete-dropdown__empty"
                                                     x-text="selectedWarehouse ? 'Aucun article disponible.' : 'Sélectionnez d\'abord un entrepôt.'">
                                                </div>
                                            </template>
                                            <template x-for="p in availableForRow(idx)" :key="p.id">
                                                <div @mousedown.prevent="selectProductInRow(idx, p)"
                                                     class="autocomplete-dropdown__item--product"
                                                     @mouseover="$el.style.background='#f8fafc'"
                                                     @mouseout="$el.style.background='white'">
                                                    <span x-text="p.name" class="autocomplete-dropdown__item-name"></span>
                                                    <span x-text="'Stock : '+p.stock" class="autocomplete-dropdown__item-stock"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    <button type="button" @click="openQuickCreate(idx)"
                                            class="btn btn--ghost btn--sm btn--icon" title="Créer un nouveau article" style="flex-shrink:0;">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    </button>
                                </div>
                            </td>
                            <td style="text-align:center;">
                                <input type="number" :name="'items['+idx+'][quantity]'"
                                       x-model.number="item.quantity" min="1"
                                       class="purchase-items__qty-input">
                            </td>
                            <td>
                                <input type="number" :name="'items['+idx+'][unit_price]'"
                                       x-model.number="item.unit_price" min="0" step="1"
                                       class="purchase-items__price-input">
                            </td>
                            <td>
                                <input type="number" :name="'items['+idx+'][selling_price]'"
                                       x-model.number="item.selling_price" min="0" step="1"
                                       class="purchase-items__price-input" placeholder="—">
                            </td>
                            <td>
                                <input type="number" :name="'items['+idx+'][wholesale_price]'"
                                       x-model.number="item.wholesale_price" min="0" step="1"
                                       class="purchase-items__price-input" placeholder="—">
                            </td>
                            <td class="purchase-items__line-total" x-text="formatMoney(item.quantity * item.unit_price)"></td>
                            <td>
                                <button type="button" @click="removeRow(idx)" class="btn btn--danger btn--sm btn--icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
</form>

{{-- Modal création rapide de article --}}
<div class="modal-overlay" x-show="quickCreateRowIdx !== null" x-cloak @click.self="quickCreateRowIdx = null" x-transition>
    <div class="modal modal--sm">
        <div class="modal__header">
            <h3>Nouveau article</h3>
            <button class="modal__close" @click="quickCreateRowIdx = null">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal__body">
            <div class="form-group">
                <label>Nom <span class="required">*</span></label>
                <input type="text" x-model="newProductName" @keydown.enter.prevent="quickCreateProduct(quickCreateRowIdx)" class="form-control" placeholder="Nom du article">
            </div>
            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label>Prix d'achat ({{ $currency }}) <span class="required">*</span></label>
                    <input type="number" x-model.number="newProductBuying" min="0" step="1" @keydown.enter.prevent="quickCreateProduct(quickCreateRowIdx)" class="form-control">
                </div>
                <div class="form-group">
                    <label>PU vente détail ({{ $currency }}) <span class="required">*</span></label>
                    <input type="number" x-model.number="newProductSelling" min="0" step="1" @keydown.enter.prevent="quickCreateProduct(quickCreateRowIdx)" class="form-control">
                </div>
                <div class="form-group">
                    <label>PU vente gros ({{ $currency }})</label>
                    <input type="number" x-model.number="newProductWholesale" min="0" step="1" @keydown.enter.prevent="quickCreateProduct(quickCreateRowIdx)" class="form-control" placeholder="Optionnel">
                </div>
                <div class="form-group">
                    <label>Catégorie <span class="required">*</span></label>
                    <select x-model="newProductCategoryId" class="form-select">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Unité <span class="required">*</span></label>
                    <select x-model="newProductUnitId" class="form-select">
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->abbreviation }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <p x-show="createProductError" x-text="createProductError" style="color:#ef4444;font-size:13px;margin-top:-8px;"></p>
            <div class="modal-footer-std">
                <button type="button" @click="quickCreateRowIdx = null" class="btn btn--light">Annuler</button>
                <button type="button" @click="quickCreateProduct(quickCreateRowIdx)" class="btn btn--primary" :disabled="creatingProduct || !newProductName.trim() || !newProductCategoryId || !newProductUnitId">
                    <span x-show="!creatingProduct">Créer & ajouter</span>
                    <span x-show="creatingProduct">...</span>
                </button>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
function purchaseForm() {
    const products   = @json($products);
    const suppliers  = @json($suppliers);
    const warehouses = @json($warehouses);
    const categories = @json($categories);
    const units      = @json($units);
    const accounts   = @json($accounts);
    let _key = 0;

    return {
        items: [],
        accounts,
        paymentAccountId: (accounts.find(a => a.is_default) ?? accounts[0])?.id ?? '',

        quickCreateRowIdx: null,
        newProductName: '', newProductBuying: 0, newProductSelling: 0, newProductWholesale: null,
        newProductCategoryId: {{ $categories->first()->id ?? 'null' }},
        newProductUnitId: {{ $units->first()->id ?? 'null' }},
        creatingProduct: false, createProductError: '',

        supplierSearch: '',
        supplierOpen: false,
        selectedSupplier: suppliers.find(s => s.id === @json($firstSupplierId)) ?? null,

        warehouseSearch: '',
        warehouseOpen: false,
        selectedWarehouse: warehouses.length > 0 ? warehouses[0] : null,

        init() {
            if (this.selectedSupplier) this.supplierSearch  = this.selectedSupplier.name;
            if (this.selectedWarehouse) this.warehouseSearch = this.selectedWarehouse.name;
            this.addRow();
        },

        get filteredSuppliers() {
            const q = this.supplierSearch.toLowerCase();
            return suppliers.filter(s => s.name.toLowerCase().includes(q));
        },

        get filteredWarehouses() {
            const q = this.warehouseSearch.toLowerCase();
            return warehouses.filter(w => w.name.toLowerCase().includes(q));
        },

        get subtotal() {
            return this.items.reduce((s, i) => s + i.quantity * i.unit_price, 0);
        },

        availableForRow(idx) {
            const selectedIds = this.items
                .filter((_, i) => i !== idx)
                .map(i => i.product_id)
                .filter(Boolean);
            const q          = (this.items[idx]?._search || '').toLowerCase();
            const warehouseId = this.selectedWarehouse?.id;
            return products
                .filter(p => {
                    if (selectedIds.includes(p.id)) return false;
                    if (q && !p.name.toLowerCase().includes(q)) return false;
                    if (warehouseId) return warehouseId in p.stocks;
                    return true;
                })
                .map(p => ({
                    ...p,
                    stock: warehouseId ? (p.stocks[warehouseId] ?? 0) : 0,
                }))
        },

        addRow() {
            this.items.push({
                _key:            ++_key,
                _search:         '',
                _open:           false,
                product_id:      null,
                item_name:       '',
                quantity:        1,
                unit_price:      0,
                selling_price:   null,
                wholesale_price: null,
            });
        },

        removeRow(idx) {
            this.items.splice(idx, 1);
            this.quickCreateRowIdx = null;
        },

        selectProductInRow(idx, p) {
            this.items[idx].product_id      = p.id;
            this.items[idx].item_name       = p.name;
            this.items[idx].unit_price      = p.buying_price || 0;
            this.items[idx].selling_price   = p.selling_price ?? 0;
            this.items[idx].wholesale_price = p.wholesale_price ?? p.selling_price ?? 0;
            this.items[idx]._search         = p.name;
            this.items[idx]._open           = false;
        },

        openQuickCreate(idx) {
            this.quickCreateRowIdx   = idx;
            this.newProductName      = this.items[idx]._search;
            this.newProductBuying    = 0;
            this.newProductSelling   = 0;
            this.newProductWholesale = null;
            this.newProductCategoryId = categories[0]?.id ?? null;
            this.newProductUnitId    = units[0]?.id ?? null;
            this.createProductError = '';
        },

        async quickCreateProduct(idx) {
            if (!this.newProductName.trim() || !this.newProductCategoryId || !this.newProductUnitId) return;
            this.creatingProduct = true;
            this.createProductError = '';
            try {
                const res = await fetch('{{ route('products.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        name: this.newProductName,
                        buying_price: this.newProductBuying || 0,
                        selling_price: this.newProductSelling || 0,
                        wholesale_price: this.newProductWholesale || null,
                        category_id: this.newProductCategoryId || null,
                        unit_id: this.newProductUnitId || null,
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    data.product.stocks = data.product.stocks || {};
                    products.push(data.product);
                    this.selectProductInRow(idx, data.product);
                    this.quickCreateRowIdx = null;
                    window.toast('Article créé avec succès.', 'success');
                } else {
                    this.createProductError = data.message || Object.values(data.errors ?? {})[0]?.[0] || 'Erreur lors de la création.';
                }
            } catch (e) {
                this.createProductError = 'Erreur réseau.';
            } finally {
                this.creatingProduct = false;
            }
        },

        selectSupplier(s) {
            this.selectedSupplier = s;
            this.supplierSearch   = s.name;
            this.supplierOpen     = false;
        },

        selectWarehouse(w) {
            this.selectedWarehouse = w;
            this.warehouseSearch   = w.name;
            this.warehouseOpen     = false;
            this.items             = [];
        },

        formatMoney(v) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(v)) + ' ' + window.CURRENCY;
        },

        submitForm(paymentType = 'pending') {
            if (!this.selectedSupplier) { window.toast('Le fournisseur est obligatoire.', 'error'); return; }
            if (this.items.length === 0) { window.toast('Ajoutez au moins un article.', 'error'); return; }
            const incomplete = this.items.filter(i => !i.product_id);
            if (incomplete.length > 0) { window.toast('Certaines lignes n\'ont pas de article sélectionné.', 'error'); return; }
            if (paymentType === 'paid' && !this.paymentAccountId) { window.toast('Sélectionnez le compte de paiement.', 'error'); return; }
            document.getElementById('payment-type-input').value = paymentType;
            document.getElementById('purchase-form').submit();
        }
    };
}
</script>
@endpush
