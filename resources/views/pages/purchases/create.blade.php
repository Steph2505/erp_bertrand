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
        <a href="{{ route('purchases.index') }}" class="btn btn--ghost">Annuler</a>
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

<div class="purchase-layout">
    <div class="purchase-layout__main">

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
                        <th>Produit</th>
                        <th class="purchase-items__col-qty">Qté</th>
                        <th class="purchase-items__col-price">Prix unit. ({{ $currency }})</th>
                        <th class="purchase-items__col-total">Total</th>
                        <th class="purchase-items__col-action"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="items.length === 0">
                        <tr>
                            <td colspan="5" class="purchase-items__empty-row">
                                Cliquez "Ajouter une ligne" pour commencer
                            </td>
                        </tr>
                    </template>
                    <template x-for="(item, idx) in items" :key="item._key">
                        <tr>
                            <td class="purchase-items__col-product">
                                <div class="autocomplete-wrap" @click.outside="item._open=false">
                                    <input type="text"
                                           x-model="item._search"
                                           @focus="item._open=true"
                                           @input="item._open=true"
                                           :placeholder="item.item_name || 'Rechercher un produit...'"
                                           class="form-control purchase-items__product-input" autocomplete="off">
                                    <input type="hidden" :name="'items['+idx+'][item_type]'" value="product">
                                    <input type="hidden" :name="'items['+idx+'][pack_id]'" value="">
                                    <input type="hidden" :name="'items['+idx+'][product_id]'" :value="item.product_id ?? ''">
                                    <input type="hidden" :name="'items['+idx+'][item_name]'" :value="item.item_name">
                                    <div x-show="item._open" x-transition class="autocomplete-dropdown autocomplete-dropdown--wide">
                                        <template x-if="availableForRow(idx).length === 0">
                                            <div class="autocomplete-dropdown__empty"
                                                 x-text="selectedWarehouse ? 'Aucun produit disponible.' : 'Sélectionnez d\'abord un entrepôt.'">
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
                            </td>
                            <td style="text-align:center;">
                                <input type="number" :name="'items['+idx+'][quantity]'"
                                       x-model.number="item.quantity" min="1" @input="recalc()"
                                       class="purchase-items__qty-input">
                            </td>
                            <td>
                                <input type="number" :name="'items['+idx+'][unit_price]'"
                                       x-model.number="item.unit_price" min="0" step="1" @input="recalc()"
                                       class="purchase-items__price-input">
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

    <div class="purchase-layout__sidebar">
        <div class="card purchase-card">
            <h3 class="purchase-summary__title">Récapitulatif</h3>
            <div class="purchase-summary__rows">
                <div class="purchase-summary__row">
                    <span class="purchase-summary__label">Sous-total</span>
                    <strong x-text="formatMoney(subtotal)"></strong>
                </div>
                <div class="purchase-summary__divider"></div>
                <div class="purchase-summary__row--total">
                    <span>Total</span>
                    <span class="purchase-summary__total-value" x-text="formatMoney(subtotal)"></span>
                </div>
            </div>
            <div class="form-group purchase-summary__note">
                <label>Note</label>
                <textarea name="note" class="form-textarea" rows="2" placeholder="Remarques..."></textarea>
            </div>
            <button type="button" @click="submitForm()" class="btn btn--primary purchase-summary__cta" :disabled="items.length === 0">
                Enregistrer l'achat
            </button>
        </div>
        <input type="hidden" id="payment-type-input" name="payment_type" value="pending">
    </div>
</div>
</form>
</div>
@endsection

@push('scripts')
<script>
function purchaseForm() {
    const products   = @json($products);
    const suppliers  = @json($suppliers);
    const warehouses = @json($warehouses);
    let _key = 0;

    return {
        items: [],
        subtotal: 0,

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
                _key:       ++_key,
                _search:    '',
                _open:      false,
                product_id: null,
                item_name:  '',
                quantity:   1,
                unit_price: 0,
            });
        },

        removeRow(idx) {
            this.items.splice(idx, 1);
            this.recalc();
        },

        selectProductInRow(idx, p) {
            this.items[idx].product_id = p.id;
            this.items[idx].item_name  = p.name;
            this.items[idx].unit_price = p.buying_price || 0;
            this.items[idx]._search    = p.name;
            this.items[idx]._open      = false;
            this.recalc();
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
            this.recalc();
        },

        recalc() {
            this.subtotal = this.items.reduce((s, i) => s + i.quantity * i.unit_price, 0);
        },

        formatMoney(v) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(v)) + ' ' + window.CURRENCY;
        },

        submitForm(paymentType = 'pending') {
            if (!this.selectedSupplier) { alert('Le fournisseur est obligatoire.'); return; }
            if (this.items.length === 0) { alert('Ajoutez au moins un article.'); return; }
            const incomplete = this.items.filter(i => !i.product_id);
            if (incomplete.length > 0) { alert('Certaines lignes n\'ont pas de produit sélectionné.'); return; }
            document.getElementById('payment-type-input').value = paymentType;
            document.getElementById('purchase-form').submit();
        }
    };
}
</script>
@endpush
