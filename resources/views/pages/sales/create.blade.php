@extends('layouts.app')
@section('title', 'Nouvelle vente')
@section('breadcrumb')
    <a href="{{ route('sales.index') }}">Ventes</a>
    <span class="sep">/</span>
    <span class="current">Nouvelle vente</span>
@endsection

@section('content')
<div x-data="saleForm()">
<form method="POST" action="{{ route('sales.store') }}" id="sale-form">
@csrf

<div class="page-header">
    <div class="page-header__title">
        <h2>Nouvelle vente</h2>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('sales.index') }}" class="btn btn--ghost">Annuler</a>
        <button type="button" @click="submitSale('draft')" class="btn btn--light" :disabled="items.length === 0">
            Brouillon
        </button>
        <button type="button" @click="submitSale('confirmed')" class="btn btn--ghost sale-create__btn-confirmed" :disabled="items.length === 0">
            Confirmé
        </button>
        <button type="button" @click="submitSale('paid')" class="btn btn--primary" :disabled="items.length === 0">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            Payé
        </button>
    </div>
</div>

<div class="sale-layout">

    <div class="sale-layout__main">

        <div class="card card--padded">
            <div class="form-grid form-grid--3">

                {{-- Client --}}
                <div class="form-group" @click.outside="customerOpen=false; customerSearch=customers.find(c=>c.id===selectedCustomer)?.name??''">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                        <label style="margin-bottom:0;">Client</label>
                        <button type="button" @click="showCreateCustomer=!showCreateCustomer;createCustomerError=''"
                                style="font-size:12px;color:#4CBB17;background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:4px;padding:0;font-weight:600;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:13px;height:13px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            <span x-text="showCreateCustomer ? 'Annuler' : 'Nouveau client'"></span>
                        </button>
                    </div>

                    {{-- Mini form création rapide --}}
                    <div x-show="showCreateCustomer" x-collapse style="margin-bottom:10px;padding:12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;">
                        <div class="form-grid form-grid--2" style="gap:8px;margin-bottom:8px;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label style="font-size:12px;">Nom <span class="required">*</span></label>
                                <input type="text" x-model="newCustomerName" @keydown.enter.prevent="quickCreateCustomer()"
                                       class="form-control" placeholder="Nom du client">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label style="font-size:12px;">Téléphone</label>
                                <input type="text" x-model="newCustomerPhone" @keydown.enter.prevent="quickCreateCustomer()"
                                       class="form-control" placeholder="Optionnel">
                            </div>
                        </div>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <button type="button" @click="quickCreateCustomer()"
                                    class="btn btn--primary btn--sm" :disabled="creatingCustomer||!newCustomerName.trim()">
                                <span x-show="!creatingCustomer">Créer & sélectionner</span>
                                <span x-show="creatingCustomer">...</span>
                            </button>
                            <span x-show="createCustomerError" x-text="createCustomerError" style="color:#ef4444;font-size:12px;"></span>
                        </div>
                    </div>

                    <div class="col-relative" x-show="!showCreateCustomer">
                        <input type="text" x-model="customerSearch"
                               @focus="customerSearch=''; customerOpen=true" @input="customerOpen=true"
                               placeholder="Client comptoir..." class="form-control" autocomplete="off">
                        <input type="hidden" name="customer_id" :value="selectedCustomer??''">
                        <div x-show="customerOpen" x-transition class="ac-dropdown">
                            <div @mousedown.prevent="selectedCustomer=null;customerSearch='';customerOpen=false"
                                 class="ac-option ac-option--separator"
                                 :style="!selectedCustomer?'background:#eff6ff;color:#3b82f6;font-weight:600':''"
                                 @mouseover="$el.style.background='#f8fafc'" @mouseout="$el.style.background=!selectedCustomer?'#eff6ff':'white'">
                                Comptoir
                            </div>
                            <template x-for="c in customers.filter(c=>c.name.toLowerCase().includes(customerSearch.toLowerCase()))" :key="c.id">
                                <div @mousedown.prevent="selectedCustomer=c.id;customerSearch=c.name;customerOpen=false"
                                     class="ac-option"
                                     :style="selectedCustomer===c.id?'background:#eff6ff;color:#3b82f6;font-weight:600':''"
                                     @mouseover="$el.style.background='#f8fafc'" @mouseout="$el.style.background=selectedCustomer===c.id?'#eff6ff':'white'">
                                    <span x-text="c.name"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>

                <div class="form-group">
                    <label>Date <span class="required">*</span></label>
                    <input type="date" name="sale_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>

                {{-- Entrepôt --}}
                <div class="form-group" @click.outside="warehouseOpen=false; warehouseSearch=warehouses.find(w=>w.id===selectedWarehouse)?.name??''">
                    <label>Entrepôt</label>
                    <div class="col-relative">
                        <input type="text" x-model="warehouseSearch"
                               @focus="warehouseSearch=''; warehouseOpen=true" @input="warehouseOpen=true"
                               placeholder="Sélectionner..." class="form-control" autocomplete="off">
                        <input type="hidden" name="warehouse_id" :value="selectedWarehouse??''">
                        <div x-show="warehouseOpen" x-transition class="ac-dropdown">
                            <div @mousedown.prevent="selectedWarehouse=null;warehouseSearch='';warehouseOpen=false;loadAllItems()"
                                 class="ac-option ac-option--separator"
                                 :style="!selectedWarehouse?'background:#eff6ff;color:#3b82f6;font-weight:600':''"
                                 @mouseover="$el.style.background='#f8fafc'" @mouseout="$el.style.background=!selectedWarehouse?'#eff6ff':'white'">
                                Aucun
                            </div>
                            <template x-for="w in warehouses.filter(w=>w.name.toLowerCase().includes(warehouseSearch.toLowerCase()))" :key="w.id">
                                <div @mousedown.prevent="selectedWarehouse=w.id;warehouseSearch=w.name;warehouseOpen=false;loadAllItems()"
                                     class="ac-option"
                                     :style="selectedWarehouse===w.id?'background:#eff6ff;color:#3b82f6;font-weight:600':''"
                                     @mouseover="$el.style.background='#f8fafc'" @mouseout="$el.style.background=selectedWarehouse===w.id?'#eff6ff':'white'">
                                    <span x-text="w.name"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="table-wrapper table-wrapper--overflow-visible">
            <div class="table-wrapper__header">
                <strong>Articles</strong>
                <span x-text="items.length + ' article(s)'" class="sale-create__count-label"></span>
                <button type="button" @click="addRow()" class="btn btn--primary btn--sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="sale-create__add-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Ajouter une ligne
                </button>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Article</th>
                        <th style="width:90px;" class="th-center">Quantité</th>
                        <th style="width:140px">Prix unit</th>
                        <th style="width:130px;" class="th-right">Sous-total</th>
                        <th style="width:36px"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="items.length === 0">
                        <tr class="sale-create__empty-row"><td colspan="5">Cliquez "Ajouter une ligne" pour commencer</td></tr>
                    </template>
                    <template x-for="(item, idx) in items" :key="item._key">
                        <tr>
                            <td class="col-article">
                                <div class="col-relative" @click.outside="item._open=false">
                                    <input type="text" x-model="item._search"
                                           @focus="item._open=true" @input="item._open=true"
                                           :placeholder="item.item_name || 'Rechercher...'"
                                           class="form-control" style="font-size:13px;" autocomplete="off">
                                    <input type="hidden" :name="'items['+idx+'][item_type]'"     :value="item.item_type">
                                    <input type="hidden" :name="'items['+idx+'][product_id]'"    :value="item.product_id ?? ''">
                                    <input type="hidden" :name="'items['+idx+'][pack_id]'"       :value="item.pack_id ?? ''">
                                    <input type="hidden" :name="'items['+idx+'][item_name]'"     :value="item.item_name">
                                    <input type="hidden" :name="'items['+idx+'][units_per_item]'" :value="item.units_per_item ?? 1">
                                    <div x-show="item._open" x-transition class="ac-dropdown ac-dropdown--wide">
                                        <template x-if="availableForRow(idx).length === 0">
                                            <div class="ac-option__empty">Aucun article trouvé</div>
                                        </template>
                                        <template x-for="p in availableForRow(idx)" :key="p.type+p.id">
                                            <div @mousedown.prevent="selectInRow(idx, p)"
                                                 class="ac-option ac-option--separator"
                                                 @mouseover="$el.style.background='#f8fafc'" @mouseout="$el.style.background='white'">
                                                <div class="ac-option__row">
                                                    <span class="ac-option__name" x-text="p.name"></span>
                                                    <span class="ac-option__stock" x-text="'Stock : '+p.stock"></span>
                                                </div>
                                                <div class="ac-option__meta">
                                                    <span x-text="formatMoney(p.price)"></span>
                                                    <template x-if="p.type === 'pack'">
                                                        <span class="badge badge--blue" style="font-size:10px;">Pack</span>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <template x-if="item.composition">
                                    <div class="item-composition" x-text="item.composition"></div>
                                </template>
                                <template x-if="item.can_be_packed && item.pack_quantity > 1">
                                    <label @click.stop class="pack-toggle">
                                        <input type="checkbox"
                                               :checked="item.is_pack_mode"
                                               @change="togglePackMode(idx)">
                                        Pack ×<span x-text="item.pack_quantity"></span>
                                    </label>
                                </template>
                            </td>
                            <td>
                                <input type="number" :name="'items['+idx+'][quantity]'"
                                       x-model.number="item.quantity" min="1" @input="recalc()"
                                       class="form-control input--qty"
                                       :style="item.stock !== null && (item.quantity * item.units_per_item) > item.stock ? 'border-color:#EF4444;color:#EF4444;' : ''">
                                <div x-show="item.stock !== null && (item.quantity * item.units_per_item) > item.stock"
                                     class="item-stock-error"
                                     x-text="item.is_pack_mode ? 'Max '+ Math.floor(item.stock / item.pack_quantity)+' packs' : 'Max '+item.stock"></div>
                            </td>
                            <td>
                                <input type="number" :name="'items['+idx+'][unit_price]'"
                                       x-model.number="item.unit_price" min="0" step="1" @input="recalc()"
                                       class="form-control input--price">
                            </td>
                            <td class="text-right font-600" x-text="formatMoney(item.quantity * item.unit_price)"></td>
                            <td>
                                <button type="button" @click="items.splice(idx,1);recalc()" class="btn btn--danger btn--sm btn--icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Récapitulatif --}}
    <div class="sale-layout__sidebar">
        <div class="card card--padded">
            <h3 class="recap__title">Récapitulatif</h3>
            <div class="recap__rows">
                <div class="recap__row">
                    <span class="recap__label-muted">Sous-total</span>
                    <strong x-text="formatMoney(subtotal)"></strong>
                </div>
                <div class="recap__divider"></div>
                <div class="recap__row--total">
                    <span>Total</span>
                    <span class="recap__total-amount" x-text="formatMoney(subtotal)"></span>
                </div>
            </div>
            <div class="form-group recap__note">
                <label>Note</label>
                <textarea name="note" class="form-textarea" rows="2" placeholder="Remarques..."></textarea>
            </div>
            <button type="button" @click="submitSale()" class="btn btn--primary recap__submit" :disabled="items.length === 0">
                Valider la vente
            </button>
        </div>

        <input type="hidden" id="status-input" name="status" value="draft">
    </div>
</div>
</form>
</div>
@endsection

@push('scripts')
<script>
function saleForm() {
    let _key = 0;

    return {
        items: [],
        allItems: [],
        subtotal: 0,
        customers: {{ Js::from($customers->map(fn($c) => ['id' => $c->id, 'name' => $c->name])) }},
        warehouses: {{ Js::from($warehouses->map(fn($w) => ['id' => $w->id, 'name' => $w->name])) }},
        customerSearch: '', customerOpen: false,
        selectedCustomer: {{ $firstCustomerId ?? 'null' }},
        showCreateCustomer: false, newCustomerName: '', newCustomerPhone: '', creatingCustomer: false, createCustomerError: '',
        warehouseSearch: '', warehouseOpen: false,
        selectedWarehouse: {{ $defaultWarehouseId ?: 'null' }},

        async init() {
            const defC = this.customers.find(c => c.id === this.selectedCustomer);
            if (defC) this.customerSearch = defC.name;
            const defW = this.warehouses.find(w => w.id === this.selectedWarehouse);
            if (defW) this.warehouseSearch = defW.name;
            await this.loadAllItems();
            this.addRow();
        },

        async loadAllItems() {
            try {
                const params = new URLSearchParams({ q: '' });
                if (this.selectedWarehouse) params.set('warehouse_id', this.selectedWarehouse);
                const r = await fetch('/sales/api/search?' + params.toString());
                if (r.ok) this.allItems = await r.json();
            } catch {}
        },

        availableForRow(idx) {
            const q  = (this.items[idx]?._search || '').toLowerCase();
            const up = this.items.filter((_, i) => i !== idx && this.items[i].item_type === 'product').map(i => i.product_id).filter(Boolean);
            const ub = this.items.filter((_, i) => i !== idx && this.items[i].item_type === 'pack').map(i => i.pack_id).filter(Boolean);
            return this.allItems.filter(p => {
                if (p.stock < 1) return false;
                if (p.type === 'product' && up.includes(p.id)) return false;
                if (p.type === 'pack'    && ub.includes(p.id)) return false;
                return !q || p.name.toLowerCase().includes(q);
            });
        },

        addRow() {
            this.items.push({ _key: ++_key, _search: '', _open: false, item_type: 'product', product_id: null, pack_id: null, item_name: '', quantity: 1, unit_price: 0, units_per_item: 1, composition: null, stock: null, can_be_packed: false, pack_quantity: 1, pack_price: 0, _base_price: 0, is_pack_mode: false });
        },

        selectInRow(idx, p) {
            Object.assign(this.items[idx], {
                item_type:    p.type,
                product_id:   p.type === 'product' ? p.id : null,
                pack_id:      p.type === 'pack'    ? p.id : null,
                item_name:    p.name,
                unit_price:   p.price,
                units_per_item: p.type === 'pack' ? (p.pack_quantity ?? 1) : 1,
                composition:  p.composition ?? null,
                stock:        p.stock,
                can_be_packed: p.type === 'product' && (p.can_be_packed || false),
                pack_quantity: p.pack_quantity ?? 1,
                pack_price:   p.pack_price ?? p.price,
                _base_price:  p.price,
                is_pack_mode: false,
                _search:      p.name,
                _open:        false,
            });
            this.recalc();
        },

        togglePackMode(idx) {
            const item = this.items[idx];
            item.is_pack_mode = !item.is_pack_mode;
            if (item.is_pack_mode) {
                item.unit_price    = item.pack_price;
                item.units_per_item = item.pack_quantity;
            } else {
                item.unit_price    = item._base_price;
                item.units_per_item = 1;
            }
            this.recalc();
        },

        async quickCreateCustomer() {
            if (!this.newCustomerName.trim()) return;
            this.creatingCustomer = true; this.createCustomerError = '';
            try {
                const res  = await fetch('{{ route('customers.store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ name: this.newCustomerName, phone: this.newCustomerPhone || null }),
                });
                const data = await res.json();
                if (data.success) {
                    this.customers.push(data.customer);
                    this.selectedCustomer = data.customer.id;
                    this.customerSearch   = data.customer.name;
                    this.showCreateCustomer = false; this.newCustomerName = ''; this.newCustomerPhone = '';
                } else {
                    this.createCustomerError = data.message || 'Erreur.';
                }
            } catch { this.createCustomerError = 'Erreur réseau.'; }
            finally   { this.creatingCustomer = false; }
        },
        recalc() { this.subtotal = this.items.reduce((s, i) => s + i.quantity * i.unit_price, 0); },
        formatMoney(v) { return new Intl.NumberFormat('fr-FR').format(Math.round(v)) + ' ' + window.CURRENCY; },

        submitSale(status = 'draft') {
            if (this.items.length === 0) { alert('Ajoutez au moins un article.'); return; }
            if (this.items.some(i => !i.item_name)) { alert('Certaines lignes sont vides.'); return; }
            const overStock = this.items.filter(i => i.stock !== null && (i.quantity * i.units_per_item) > i.stock);
            if (overStock.length > 0) {
                alert('Stock insuffisant :\n' + overStock.map(i => `• ${i.item_name} : demandé ${i.quantity}, disponible ${i.stock}`).join('\n'));
                return;
            }
            document.getElementById('status-input').value = status;
            document.getElementById('sale-form').submit();
        }
    };
}
</script>
@endpush
