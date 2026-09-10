@extends('layouts.app')
@section('title', 'Ajustements de stock')
@section('breadcrumb')<span class="current">Stock — Ajustements</span>@endsection

@section('content')
<div x-data="adjPage()" x-init="fetch()">

<div class="page-header">
    <div class="page-header__title">
        <h2>Ajustements de stock</h2>
        <p x-text="total + ' ajustement(s)'">—</p>
    </div>
    <div class="page-header__actions">
        <button @click="showModal = true" class="btn btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nouvel ajustement
        </button>
    </div>
</div>

<div class="table-wrapper" style="margin-bottom:16px;padding:14px 20px;">
    <div class="filters-bar">
        <div class="input-group" style="flex:1;min-width:160px;">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <input type="text" x-model="search" @input.debounce.400ms="resetList()" class="form-control" placeholder="Référence...">
        </div>
        <select x-model="filterType" @change="resetList()" class="form-select">
            <option value="">Tous types</option>
            <option value="addition">+ Entrée</option>
            <option value="subtraction">− Sortie</option>
        </select>
        <button x-show="search || filterType" @click="search='';filterType='';resetList()" class="btn btn--ghost btn--sm">✕ Effacer</button>
        <span x-show="loading" class="text-muted text-sm">Chargement...</span>
    </div>
</div>

<div class="table-wrapper">
    <div style="position:relative;">
        <div x-show="loading && rows.length > 0" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
            <svg style="width:28px;height:28px;color:#1749B3;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        </div>
        <table class="data-table">
            <thead><tr><th>Référence</th><th>Type</th><th>Entrepôt</th><th>Date</th><th>Raison</th><th>Créé par</th></tr></thead>
            <tbody>
                <template x-if="loading && rows.length === 0">
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:#64748B;">Chargement...</td></tr>
                </template>
                <template x-if="!loading && rows.length === 0">
                    <tr><td colspan="6" class="table-empty-cell--sm">Aucun ajustement</td></tr>
                </template>
                <template x-for="a in rows" :key="a.id">
                    <tr>
                        <td class="font-600" x-text="a.reference"></td>
                        <td><span class="badge" :class="a.type_badge" x-text="a.type_label"></span></td>
                        <td x-text="a.warehouse"></td>
                        <td x-text="a.adjustment_date"></td>
                        <td x-text="a.reason"></td>
                        <td x-text="a.created_by"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
    <div class="table-wrapper__footer">
        <span x-text="listFrom + '–' + listTo + ' sur ' + total" class="text-muted text-sm"></span>
        <div style="display:flex;gap:4px;" x-show="lastPage > 1">
            <button @click="goTo(currentPage-1)" :disabled="currentPage<=1||loading" class="btn btn--ghost btn--sm btn--icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg></button>
            <template x-for="p in pages" :key="p"><button @click="p!=='…'&&goTo(p)" class="btn btn--sm" :class="p===currentPage?'btn--primary':'btn--ghost'" :disabled="p==='…'||loading" x-text="p" style="min-width:34px;justify-content:center;"></button></template>
            <button @click="goTo(currentPage+1)" :disabled="currentPage>=lastPage||loading" class="btn btn--ghost btn--sm btn--icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg></button>
        </div>
    </div>
</div>

{{-- Modal ajustement --}}
<div class="modal-overlay" x-show="showModal" x-cloak @click.self="showModal = false" x-transition>
    <div class="modal modal--lg">
        <div class="modal__header">
            <h3>Nouvel ajustement de stock</h3>
            <button class="modal__close" @click="showModal = false">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('stock-adjustments.store') }}" class="modal__body">
            @csrf
            <div class="form-grid form-grid--3" style="margin-bottom:16px;">
                <div class="form-group">
                    <label>Type <span class="required">*</span></label>
                    <select name="type" class="form-select" required>
                        <option value="addition">+ Entrée (ajout)</option>
                        <option value="subtraction">− Sortie (retrait)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Entrepôt</label>
                    <select name="warehouse_id" class="form-select">
                        <option value="">-- Sélectionner --</option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Date <span class="required">*</span></label>
                    <input type="date" name="adjustment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
            </div>
            <div class="form-group">
                <label>Raison / Note</label>
                <input type="text" name="reason" class="form-control" placeholder="Ex: Inventaire, casse, vol...">
            </div>
            <h4 class="text-sm font-600" style="margin-bottom:10px;">Articles à ajuster</h4>
            <div class="form-grid form-grid--3" style="margin-bottom:10px;align-items:flex-end;">
                <div class="form-group" style="margin-bottom:0;grid-column:span 2">
                    <label>Produit</label>
                    <select id="adj-product" x-model="newItem.product_id" class="form-select">
                        <option value="">-- Sélectionner --</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" data-name="{{ $p->display_name }}">{{ $p->display_name }} (stock : {{ $p->stock_quantity }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label>Quantité</label>
                    <input type="number" x-model.number="newItem.quantity" min="1" class="form-control">
                </div>
                <div>
                    <button type="button" @click="addItem()" class="btn btn--primary">+ Ajouter</button>
                </div>
            </div>
            <table class="data-table" style="margin-bottom:16px;">
                <thead><tr><th>Produit</th><th class="th-center" style="width:100px">Quantité</th><th style="width:36px"></th></tr></thead>
                <tbody>
                    <template x-if="items.length === 0"><tr><td colspan="3" class="table-empty-cell--sm">Aucun article</td></tr></template>
                    <template x-for="(item, idx) in items" :key="idx">
                        <tr>
                            <td x-text="item.name"></td>
                            <td style="text-align:center">
                                <input type="number" :name="'items['+idx+'][quantity]'" x-model.number="item.quantity" min="1" class="purchase-items__qty-input">
                                <input type="hidden" :name="'items['+idx+'][product_id]'" :value="item.product_id">
                            </td>
                            <td><button type="button" @click="items.splice(idx,1)" class="btn btn--danger btn--sm btn--icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <div class="modal-footer-std">
                <button type="button" @click="showModal = false" class="btn btn--ghost">Annuler</button>
                <button type="submit" class="btn btn--primary" :disabled="items.length === 0">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script>
function adjPage() {
    const CSRF = () => document.querySelector('meta[name=csrf-token]').content;
    return {
        // Listing
        rows: [], total: 0, listFrom: 0, listTo: 0, currentPage: 1, lastPage: 1, loading: true,
        search: '', filterType: '',

        get pages() {
            const t = this.lastPage, c = this.currentPage;
            if (t <= 7) return Array.from({ length: t }, (_, i) => i + 1);
            const p = [1];
            if (c > 3) p.push('…');
            for (let i = Math.max(2, c-1); i <= Math.min(t-1, c+1); i++) p.push(i);
            if (c < t-2) p.push('…');
            p.push(t); return p;
        },

        resetList() { this.currentPage = 1; this.fetch(); },

        goTo(page) {
            if (page < 1 || page > this.lastPage) return;
            this.currentPage = page; this.fetch();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        async fetch() {
            this.loading = true;
            const params = new URLSearchParams({ page: this.currentPage });
            if (this.search)     params.set('search', this.search);
            if (this.filterType) params.set('type',   this.filterType);
            try {
                const res  = await fetch('{{ route('stock-adjustments.api.list') }}?' + params, { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF() } });
                const data = await res.json();
                this.rows = data.data; this.total = data.total;
                this.listFrom = data.from; this.listTo = data.to;
                this.currentPage = data.current_page; this.lastPage = data.last_page;
            } catch(e) { console.error(e); }
            finally { this.loading = false; }
        },

        // Modal
        showModal: false, items: [], newItem: { product_id: '', quantity: 1 },

        addItem() {
            const sel = document.getElementById('adj-product');
            if (!this.newItem.product_id) { window.toast('Sélectionnez un produit.', 'error'); return; }
            const opt = sel.options[sel.selectedIndex];
            this.items.push({ product_id: this.newItem.product_id, name: opt.dataset.name, quantity: this.newItem.quantity || 1 });
            this.newItem = { product_id: '', quantity: 1 };
            sel.value = '';
        },
    };
}
</script>
@endpush
