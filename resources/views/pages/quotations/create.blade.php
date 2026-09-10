@extends('layouts.app')
@section('title', 'Nouveau devis')
@section('breadcrumb')
<a href="{{ route('quotations.index') }}">Devis</a>
<span class="current">Nouveau</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>Nouveau devis</h2>
    </div>
</div>

<form method="POST" action="{{ route('quotations.store') }}" x-data="quoteForm()" @submit.prevent="submitForm">
    @csrf
    <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">

        {{-- Colonne gauche --}}
        <div>
            <div class="card" style="padding:24px;margin-bottom:16px;">
                <div class="form-grid form-grid--3" style="gap:16px;">
                    <div class="form-group">
                        <label>Client</label>
                        <select name="customer_id" class="form-select">
                            <option value="">Client comptoir</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date du devis <span class="required">*</span></label>
                        <input type="date" name="quotation_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Date d'expiration</label>
                        <input type="date" name="expiry_date" class="form-control">
                    </div>
                </div>
            </div>

            <div class="table-wrapper" style="overflow:visible;margin-bottom:16px;">
                <div class="table-wrapper__header">
                    <strong>Articles</strong>
                    <span x-text="items.length + ' ligne(s)'" style="font-size:13px;color:#64748B;"></span>
                    <div style="display:flex;gap:8px;">
                        <button type="button" @click="addRow()" class="btn btn--primary btn--sm">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Ajouter une ligne
                        </button>
                    </div>
                </div>
                <table class="data-table">
                    <thead><tr>
                        <th>Désignation</th>
                        <th style="text-align:center;width:80px">Qté</th>
                        <th style="text-align:right;width:150px">Prix unit. ({{ $currency }})</th>
                        <th style="text-align:right;width:130px">Total</th>
                        <th style="width:36px"></th>
                    </tr></thead>
                    <tbody>
                        <template x-if="items.length === 0">
                            <tr><td colspan="5" style="text-align:center;padding:30px;color:#94A3B8;">Cliquez "Ajouter une ligne" pour commencer</td></tr>
                        </template>
                        <template x-for="(item, idx) in items" :key="item._key">
                            <tr>
                                <td style="min-width:220px;">
                                    <template x-if="!item._free">
                                        <div style="position:relative;" @click.outside="item._open=false">
                                            <input type="text" x-model="item._search"
                                                   @focus="item._open=true" @input="item._open=true"
                                                   :placeholder="item.name || 'Rechercher...'"
                                                   class="form-control" style="font-size:13px;" autocomplete="off">
                                            <input type="hidden" :name="'items['+idx+'][product_id]'" :value="item.product_id ?? ''">
                                            <input type="hidden" :name="'items['+idx+'][type]'" value="product">
                                            <div x-show="item._open" x-transition
                                                 style="position:absolute;z-index:9999;background:white;border:1.5px solid #e2e8f0;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);max-height:240px;overflow-y:auto;width:100%;min-width:260px;top:calc(100% + 4px);left:0;">
                                                <template x-if="availableForRow(idx).length === 0">
                                                    <div style="padding:12px 14px;color:#94a3b8;font-size:13px;">Aucun article trouvé</div>
                                                </template>
                                                <template x-for="p in availableForRow(idx)" :key="p.id">
                                                    <div @mousedown.prevent="selectInRow(idx, p)"
                                                         style="padding:9px 14px;cursor:pointer;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;"
                                                         @mouseover="$el.style.background='#f8fafc'" @mouseout="$el.style.background='white'">
                                                        <span style="font-weight:500;font-size:13px;" x-text="p.name"></span>
                                                        <span style="font-size:11px;color:#94a3b8;" x-text="fmt(p.selling_price)"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="item._free">
                                        <div>
                                            <input type="hidden" :name="'items['+idx+'][product_id]'" value="">
                                            <input type="hidden" :name="'items['+idx+'][type]'" value="product">
                                            <input type="text" :name="'items['+idx+'][name]'" x-model="item.name"
                                                   class="form-control" style="font-size:13px;" placeholder="Désignation libre">
                                        </div>
                                    </template>
                                    <input type="hidden" :name="'items['+idx+'][name]'" :value="item.name" x-show="!item._free">
                                </td>
                                <td>
                                    <input type="number" :name="'items['+idx+'][qty]'"
                                           x-model.number="item.qty" min="0.01" step="0.01" @input="recalc()"
                                           class="form-control" style="text-align:center;">
                                </td>
                                <td>
                                    <input type="number" :name="'items['+idx+'][price]'"
                                           x-model.number="item.price" min="0" step="1" @input="recalc()"
                                           class="form-control" style="text-align:right;">
                                </td>
                                <td style="text-align:right;font-weight:600;" x-text="fmt(item.qty * item.price)"></td>
                                <td>
                                    <button type="button" @click="items.splice(idx,1)" class="btn btn--danger btn--sm btn--icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div id="items-container"></div>
            </div>

            <div class="card" style="padding:24px;">
                <div class="form-group">
                    <label>Note / Conditions</label>
                    <textarea name="note" class="form-control" rows="3" placeholder="Conditions, délai de livraison, validité..."></textarea>
                </div>
            </div>
        </div>

        {{-- Colonne droite --}}
        <div>
            <div class="card" style="padding:24px;margin-bottom:16px;">
                <h3 style="font-size:14px;font-weight:600;margin-bottom:16px;">Récapitulatif</h3>
                <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:14px;">
                    <span>Sous-total</span>
                    <span x-text="fmt(subtotal)"></span>
                </div>
                <div class="form-group" style="margin-bottom:8px;">
                    <label style="font-size:12px;">Remise ({{ $currency }})</label>
                    <input type="number" x-model.number="discount" min="0" step="1" class="form-control" @input="recalc()">
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px 0;font-size:16px;font-weight:800;border-top:2px solid #e2e8f0;margin-top:8px;">
                    <span>TOTAL</span>
                    <span style="color:#3b82f6;" x-text="fmt(total)"></span>
                </div>
            </div>
            <button type="submit" class="btn btn--primary" style="width:100%;justify-content:center;padding:14px;font-size:15px;" :disabled="items.length === 0">
                Enregistrer le devis
            </button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
function quoteForm() {
    let _key = 0;
    let allProducts = [];

    return {
        items: [],
        discount: 0,

        get subtotal() { return this.items.reduce((s, i) => s + i.qty * i.price, 0); },
        get total()    { return Math.max(0, this.subtotal - this.discount); },

        async init() {
            try { const r = await fetch('/sales/api/search?q='); if (r.ok) allProducts = (await r.json()).filter(p => p.type === 'product'); } catch {}
            this.addRow();
        },

        availableForRow(idx) {
            const q   = (this.items[idx]?._search || '').toLowerCase();
            const used = this.items.filter((_, i) => i !== idx).map(i => i.product_id).filter(Boolean);
            return allProducts.filter(p => !used.includes(p.id) && (!q || p.name.toLowerCase().includes(q)));
        },

        addRow() {
            this.items.push({ _key: ++_key, _search: '', _open: false, _free: false, product_id: null, name: '', qty: 1, price: 0 });
        },

        selectInRow(idx, p) {
            Object.assign(this.items[idx], { product_id: p.id, name: p.name, price: p.price, _search: p.name, _open: false });
        },

        recalc() {},
        fmt(v) { return new Intl.NumberFormat('fr-FR').format(Math.round(v)) + ' ' + window.CURRENCY; },

        submitForm() {
            if (this.items.length === 0) { window.toast('Ajoutez au moins un article.', 'error'); return; }
            if (this.items.some(i => !i.name)) { window.toast('Certaines lignes n\'ont pas de désignation.', 'error'); return; }
            const container = document.getElementById('items-container');
            container.innerHTML = '';
            const add = (n, v) => { const inp = document.createElement('input'); inp.type = 'hidden'; inp.name = n; inp.value = v; container.appendChild(inp); };
            this.items.forEach((item, i) => {
                add(`items[${i}][product_id]`, item.product_id ?? '');
                add(`items[${i}][name]`,       item.name);
                add(`items[${i}][type]`,       'product');
                add(`items[${i}][qty]`,        item.qty);
                add(`items[${i}][price]`,      item.price);
            });
            add('discount', this.discount);
            this.$el.submit();
        },
    };
}
</script>
@endpush
