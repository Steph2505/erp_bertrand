@extends('layouts.app')
@section('title', 'Modifier : ' . $sale->reference)
@section('breadcrumb')
    <a href="{{ route('sales.index') }}">Ventes</a>
    <span class="sep">/</span>
    <a href="{{ route('sales.show', $sale) }}">{{ $sale->reference }}</a>
    <span class="sep">/</span>
    <span class="current">Modifier</span>
@endsection

@section('content')
<div x-data="saleEditForm()" x-init="recalc()">
<form method="POST" action="{{ route('sales.update', $sale) }}" id="sale-form">
@csrf @method('PUT')

<div class="page-header">
    <div class="page-header__title">
        <h2>Modifier le brouillon</h2>
        <p style="color:#64748b;">{{ $sale->reference }}</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('sales.show', $sale) }}" class="btn btn--ghost">Annuler</a>
        <button type="button" @click="submitSale()" class="btn btn--primary" :disabled="items.length === 0">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            Mettre à jour
        </button>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">

    <div style="display:flex;flex-direction:column;gap:16px;">

        <div class="card" style="padding:20px;">
            <div class="form-grid form-grid--3">

                {{-- Client --}}
                <div class="form-group" @click.outside="customerOpen=false; customerSearch=customers.find(c=>c.id===selectedCustomer)?.name??''">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                        <label style="margin-bottom:0;">Client</label>
                        <button type="button" @click="showCreateCustomer=!showCreateCustomer;createCustomerError=''"
                                style="font-size:12px;color:#1749B3;background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:4px;padding:0;font-weight:600;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:13px;height:13px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            <span x-text="showCreateCustomer ? 'Annuler' : 'Nouveau client'"></span>
                        </button>
                    </div>
                    <div x-show="showCreateCustomer" x-collapse style="margin-bottom:10px;padding:12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;">
                        <div class="form-grid form-grid--2" style="gap:8px;margin-bottom:8px;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label style="font-size:12px;">Nom <span class="required">*</span></label>
                                <input type="text" x-model="newCustomerName" @keydown.enter.prevent="quickCreateCustomer()" class="form-control" placeholder="Nom du client">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label style="font-size:12px;">Téléphone</label>
                                <input type="text" x-model="newCustomerPhone" @keydown.enter.prevent="quickCreateCustomer()" class="form-control" placeholder="Optionnel">
                            </div>
                        </div>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <button type="button" @click="quickCreateCustomer()" class="btn btn--primary btn--sm" :disabled="creatingCustomer||!newCustomerName.trim()">
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
                    <input type="date" name="sale_date" class="form-control"
                           value="{{ old('sale_date', $sale->sale_date->format('Y-m-d')) }}" required>
                </div>

                {{-- Entrepôt --}}
                <div class="form-group" @click.outside="warehouseOpen=false; warehouseSearch=warehouses.find(w=>w.id===selectedWarehouse)?.name??''">
                    <label>Entrepôt</label>
                    <div style="position:relative;">
                        <input type="text" x-model="warehouseSearch"
                               @focus="warehouseSearch=''; warehouseOpen=true" @input="warehouseOpen=true"
                               placeholder="Sélectionner..." class="form-control" autocomplete="off">
                        <input type="hidden" name="warehouse_id" :value="selectedWarehouse??''">
                        <div x-show="warehouseOpen" x-transition
                             style="position:absolute;z-index:9999;background:white;border:1.5px solid #e2e8f0;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.12);max-height:200px;overflow-y:auto;width:100%;top:calc(100% + 4px);">
                            <div @mousedown.prevent="selectedWarehouse=null;warehouseSearch='';warehouseOpen=false;loadAllItems()"
                                 style="padding:10px 14px;cursor:pointer;font-size:13px;border-bottom:1px solid #f1f5f9;"
                                 :style="!selectedWarehouse?'background:#eff6ff;color:#3b82f6;font-weight:600':''"
                                 @mouseover="$el.style.background='#f8fafc'" @mouseout="$el.style.background=!selectedWarehouse?'#eff6ff':'white'">
                                Aucun
                            </div>
                            <template x-for="w in warehouses.filter(w=>w.name.toLowerCase().includes(warehouseSearch.toLowerCase()))" :key="w.id">
                                <div @mousedown.prevent="selectedWarehouse=w.id;warehouseSearch=w.name;warehouseOpen=false;loadAllItems()"
                                     style="padding:10px 14px;cursor:pointer;font-size:13px;"
                                     :style="selectedWarehouse===w.id?'background:#eff6ff;color:#3b82f6;font-weight:600':''"
                                     @mouseover="$el.style.background='#f8fafc'" @mouseout="$el.style.background=selectedWarehouse===w.id?'#eff6ff':'white'">
                                    <span x-text="w.name"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

            </div>
            <div class="form-group" style="margin-bottom:0">
                <label>Note</label>
                <textarea name="note" class="form-textarea" rows="2">{{ old('note', $sale->note) }}</textarea>
            </div>
        </div>

        <div class="table-wrapper" style="overflow:visible;">
            <div class="table-wrapper__header">
                <strong>Articles</strong>
                <span x-text="items.length + ' article(s)'" style="font-size:13px;color:#64748B;"></span>
                <button type="button" @click="addRow()" class="btn btn--primary btn--sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Ajouter une ligne
                </button>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Article</th>
                        <th style="width:90px;text-align:center">Qté</th>
                        <th style="width:140px">Prix unit. ({{ $currency }})</th>
                        <th style="width:130px;text-align:right">Sous-total</th>
                        <th style="width:36px"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="items.length === 0">
                        <tr><td colspan="5" style="text-align:center;padding:40px;color:#94A3B8;">Cliquez "Ajouter une ligne"</td></tr>
                    </template>
                    <template x-for="(item, idx) in items" :key="item._key">
                        <tr>
                            <td style="min-width:220px;">
                                <div style="position:relative;" @click.outside="item._open=false">
                                    <input type="text" x-model="item._search"
                                           @focus="item._open=true" @input="item._open=true"
                                           :placeholder="item.item_name || 'Rechercher...'"
                                           class="form-control" style="font-size:13px;" autocomplete="off">
                                    <input type="hidden" :name="'items['+idx+'][item_type]'"      :value="item.item_type">
                                    <input type="hidden" :name="'items['+idx+'][product_id]'"     :value="item.product_id ?? ''">
                                    <input type="hidden" :name="'items['+idx+'][pack_id]'"        :value="item.pack_id ?? ''">
                                    <input type="hidden" :name="'items['+idx+'][item_name]'"      :value="item.item_name">
                                    <input type="hidden" :name="'items['+idx+'][units_per_item]'" :value="item.units_per_item ?? 1">
                                    <div x-show="item._open" x-transition
                                         style="position:absolute;z-index:9999;background:white;border:1.5px solid #e2e8f0;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);max-height:260px;overflow-y:auto;width:100%;min-width:280px;top:calc(100% + 4px);left:0;">
                                        <template x-if="availableForRow(idx).length === 0">
                                            <div style="padding:12px 14px;color:#94a3b8;font-size:13px;">Aucun article trouvé</div>
                                        </template>
                                        <template x-for="p in availableForRow(idx)" :key="p.type+p.id">
                                            <div @mousedown.prevent="selectInRow(idx, p)"
                                                 style="padding:9px 14px;cursor:pointer;border-bottom:1px solid #f1f5f9;"
                                                 @mouseover="$el.style.background='#f8fafc'" @mouseout="$el.style.background='white'">
                                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                                    <span style="font-weight:500;font-size:13px;" x-text="p.name"></span>
                                                    <span style="font-size:11px;color:#94a3b8;" x-text="'Stock : '+p.stock"></span>
                                                </div>
                                                <div style="font-size:11px;color:#64748B;margin-top:2px;display:flex;gap:8px;">
                                                    <span x-text="formatMoney(p.price)"></span>
                                                    <template x-if="p.type === 'pack'"><span class="badge badge--blue" style="font-size:10px;">Pack</span></template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <template x-if="item.item_type === 'pack'"><span class="badge badge--blue" style="font-size:10px;">Pack</span></template>
                                <template x-if="item.can_be_packed && item.pack_quantity > 1">
                                    <label @click.stop style="display:inline-flex;align-items:center;gap:5px;font-size:11px;margin-top:5px;cursor:pointer;color:#15803d;font-weight:600;padding:3px 8px;background:#f0fdf4;border-radius:5px;border:1px solid #bbf7d0;">
                                        <input type="checkbox" :checked="item.is_pack_mode" @change="togglePackMode(idx)" style="cursor:pointer;accent-color:#1749B3;">
                                        Pack ×<span x-text="item.pack_quantity"></span>
                                    </label>
                                </template>
                            </td>
                            <td style="text-align:center;">
                                <input type="number" :name="'items['+idx+'][quantity]'"
                                       x-model.number="item.quantity" min="1" @input="recalc()"
                                       :style="item.stock !== null && (item.quantity * item.units_per_item) > item.stock ? 'border-color:#C4231A;color:#C4231A;' : ''"
                                       class="form-control" style="width:90px;text-align:center;">
                                <div x-show="item.stock !== null && (item.quantity * item.units_per_item) > item.stock"
                                     style="font-size:11px;color:#C4231A;margin-top:2px;"
                                     x-text="item.is_pack_mode ? 'Max '+ Math.floor(item.stock / item.pack_quantity)+' packs' : 'Max '+item.stock"></div>
                            </td>
                            <td>
                                <input type="number" :name="'items['+idx+'][unit_price]'"
                                       x-model.number="item.unit_price" min="0" step="1" @input="recalc()"
                                       style="width:130px;padding:5px 8px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:13px;">
                            </td>
                            <td style="text-align:right;font-weight:600;" x-text="formatMoney(item.quantity * item.unit_price)"></td>
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
    <div style="position:sticky;top:80px;">
        <div class="card" style="padding:20px;">
            <h3 style="font-size:15px;font-weight:600;margin-bottom:16px;">Récapitulatif</h3>
            <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px;">
                <div style="display:flex;justify-content:space-between;font-size:14px;">
                    <span style="color:#64748B;">Sous-total</span>
                    <strong x-text="formatMoney(subtotal)"></strong>
                </div>
                <div style="height:1px;background:#e2e8f0;"></div>
                <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:700;">
                    <span>Total</span>
                    <span style="color:#1749B3;" x-text="formatMoney(subtotal)"></span>
                </div>
            </div>
            <button type="button" @click="submitSale()" class="btn btn--primary" style="width:100%;justify-content:center;" :disabled="items.length === 0">
                Mettre à jour
            </button>
        </div>
    </div>
</div>
</form>
</div>
@endsection

@push('scripts')
<script>
function saleEditForm() {
    let _key = 0;
    const existingItems = {{ Js::from($sale->items->map(fn($i) => [
        'item_type'      => $i->item_type,
        'product_id'     => $i->product_id,
        'pack_id'        => $i->pack_id,
        'item_name'      => $i->item_name,
        'quantity'       => $i->quantity,
        'unit_price'     => (float) $i->unit_price,
        'units_per_item' => (int) ($i->units_per_item ?? 1),
        'can_be_packed'  => (bool) ($i->product?->can_be_packed ?? false),
        'pack_quantity'  => (int) ($i->product?->pack_quantity ?? 1),
        'pack_price'     => (float) ($i->product?->pack_price ?? $i->unit_price),
        'base_price'     => (float) ($i->product?->selling_price ?? $i->unit_price),
        'is_pack_mode'   => ((int) ($i->units_per_item ?? 1)) > 1,
    ])) }};

    return {
        subtotal: 0,
        customers: {{ Js::from($customers->map(fn($c) => ['id' => $c->id, 'name' => $c->name])) }},
        warehouses: {{ Js::from($warehouses->map(fn($w) => ['id' => $w->id, 'name' => $w->name])) }},
        customerSearch: '{{ addslashes($sale->customer?->name ?? '') }}',
        customerOpen: false,
        selectedCustomer: {{ $sale->customer_id ?? 'null' }},
        showCreateCustomer: false, newCustomerName: '', newCustomerPhone: '', creatingCustomer: false, createCustomerError: '',
        warehouseSearch: '{{ addslashes($sale->warehouse?->name ?? '') }}',
        warehouseOpen: false,
        selectedWarehouse: {{ $sale->warehouse_id ?? 'null' }},
        allItems: [],
        items: [],

        async init() {
            await this.loadAllItems();
            existingItems.forEach(i => {
                // stock depuis allItems si disponible (produit en stock), sinon null
                const live = this.allItems.find(p => p.type === 'product' && p.id === i.product_id);
                this.items.push({
                    _key:          ++_key,
                    _search:       i.item_name,
                    _open:         false,
                    ...i,
                    composition:   null,
                    stock:         live?.stock ?? null,
                    _base_price:   i.base_price,
                });
            });
            if (this.items.length === 0) this.addRow();
            this.recalc();
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
                item_type:     p.type,
                product_id:    p.type === 'product' ? p.id : null,
                pack_id:       p.type === 'pack'    ? p.id : null,
                item_name:     p.name,
                unit_price:    p.price,
                units_per_item: p.type === 'pack' ? (p.pack_quantity ?? 1) : 1,
                composition:   p.composition ?? null,
                stock:         p.stock,
                can_be_packed: p.type === 'product' && (p.can_be_packed || false),
                pack_quantity: p.pack_quantity ?? 1,
                pack_price:    p.pack_price ?? p.price,
                _base_price:   p.price,
                is_pack_mode:  false,
                _search:       p.name,
                _open:         false,
            });
            this.recalc();
        },

        togglePackMode(idx) {
            const item = this.items[idx];
            item.is_pack_mode = !item.is_pack_mode;
            item.unit_price    = item.is_pack_mode ? item.pack_price  : item._base_price;
            item.units_per_item = item.is_pack_mode ? item.pack_quantity : 1;
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

        submitSale() {
            if (this.items.length === 0) { window.toast('Ajoutez au moins un article.', 'error'); return; }
            if (this.items.some(i => !i.item_name)) { window.toast('Certaines lignes sont vides.', 'error'); return; }
            document.getElementById('sale-form').submit();
        }
    };
}
</script>
@endpush
