@extends('layouts.app')
@section('title', 'Transferts de stock')
@section('breadcrumb')<span class="current">Stock — Transferts</span>@endsection

@section('content')
<div x-data="tfrPage()" x-init="fetch()">

<div class="page-header">
    <div class="page-header__title">
        <h2>Transferts de stock</h2>
        <p x-text="total + ' transfert(s)'">—</p>
    </div>
    <div class="page-header__actions">
        <button @click="showModal = true" class="btn btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nouveau transfert
        </button>
    </div>
</div>

{{-- Filtres --}}
<div class="table-wrapper" style="margin-bottom:16px;padding:14px 20px;">
    <div class="filters-bar">
        <div class="input-group" style="flex:1;min-width:160px;">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <input type="text" x-model="search" @input.debounce.400ms="resetList()" class="form-control" placeholder="Référence...">
        </div>
        <select x-model="filterStatus" @change="resetList()" class="form-select">
            <option value="">Tous statuts</option>
            <option value="pending">En attente</option>
            <option value="in_transit">En transit</option>
            <option value="received">Reçu</option>
        </select>
        <select x-model="filterWarehouse" @change="resetList()" class="form-select">
            <option value="">Tous entrepôts</option>
            @foreach($warehouses as $w)
                <option value="{{ $w->id }}">{{ $w->name }}</option>
            @endforeach
        </select>
        <button x-show="search || filterStatus || filterWarehouse" @click="search='';filterStatus='';filterWarehouse='';resetList()" class="btn btn--ghost btn--sm">✕ Effacer</button>
        <span x-show="loading" class="text-muted text-sm">Chargement...</span>
    </div>
</div>

{{-- Table --}}
<div class="table-wrapper">
    <div style="position:relative;">
        <div x-show="loading && rows.length > 0" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
            <svg style="width:28px;height:28px;color:#4CBB17;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        </div>
        <table class="data-table">
            <thead><tr><th>Référence</th><th>De</th><th>Vers</th><th>Date</th><th>Statut</th><th>Note</th></tr></thead>
            <tbody>
                <template x-if="loading && rows.length === 0">
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:#64748B;">Chargement...</td></tr>
                </template>
                <template x-if="!loading && rows.length === 0">
                    <tr><td colspan="6" class="table-empty-cell--sm">Aucun transfert</td></tr>
                </template>
                <template x-for="t in rows" :key="t.id">
                    <tr>
                        <td class="font-600" x-text="t.reference"></td>
                        <td x-text="t.from"></td>
                        <td x-text="t.to"></td>
                        <td x-text="t.transfer_date"></td>
                        <td><span x-html="t.status_badge"></span></td>
                        <td x-text="t.note"></td>
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

{{-- Modal --}}
<div class="modal-overlay" x-show="showModal" x-cloak @click.self="showModal = false" x-transition>
    <div class="modal modal--lg">
        <div class="modal__header">
            <h3>Nouveau transfert de stock</h3>
            <button class="modal__close" @click="showModal = false">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('stock-transfers.store') }}" class="modal__body">
            @csrf
            <div class="form-grid form-grid--3" style="margin-bottom:16px;">
                <div class="form-group">
                    <label>Entrepôt source <span class="required">*</span></label>
                    <select name="from_warehouse_id" x-model="fromId" class="form-select" required @change="toId=''; items=[]">
                        <option value="">-- Sélectionner --</option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Entrepôt destination <span class="required">*</span></label>
                    <select name="to_warehouse_id" x-model="toId" class="form-select" required>
                        <option value="">-- Sélectionner --</option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}" :disabled="fromId == {{ $w->id }}" :style="fromId == {{ $w->id }} ? 'display:none' : ''">{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Date <span class="required">*</span></label>
                    <input type="date" name="transfer_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
            </div>
            <div class="form-group">
                <label>Note</label>
                <input type="text" name="note" class="form-control" placeholder="Raison du transfert...">
            </div>
            <div class="table-wrapper" style="overflow:visible;margin-bottom:16px;">
                <div class="table-wrapper__header">
                    <strong>Articles à transférer</strong>
                    <span x-text="items.length + ' article(s)'" class="text-muted text-sm"></span>
                    <button type="button" @click="addRow()" class="btn btn--primary btn--sm" :disabled="!fromId">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Ajouter une ligne
                    </button>
                </div>
                <template x-if="!fromId"><p class="text-muted text-sm" style="padding:12px 16px;">Sélectionnez d'abord un entrepôt source.</p></template>
                <table class="data-table">
                    <thead><tr><th>Produit</th><th class="th-center" style="width:110px">Quantité</th><th style="width:36px"></th></tr></thead>
                    <tbody>
                        <template x-if="items.length === 0"><tr><td colspan="3" class="table-empty-cell--sm">Aucun article</td></tr></template>
                        <template x-for="(item, idx) in items" :key="item._key">
                            <tr :style="item.quantity > item.available ? 'background:#fef2f2;' : ''">
                                <td style="min-width:200px;">
                                    <div class="col-relative" @click.outside="item._open=false">
                                        <input type="text" x-model="item._search" @focus="item._open=true" @input="item._open=true"
                                               :placeholder="item.name || 'Rechercher...'" class="form-control" style="font-size:13px;" autocomplete="off">
                                        <input type="hidden" :name="'items['+idx+'][product_id]'" :value="item.product_id">
                                        <div x-show="item._open" x-transition class="ac-dropdown ac-dropdown--wide">
                                            <template x-if="availableForRow(idx).length === 0"><div class="ac-option__empty">Aucun produit disponible</div></template>
                                            <template x-for="p in availableForRow(idx)" :key="p.id">
                                                <div @mousedown.prevent="selectInRow(idx, p)" class="ac-option ac-option--separator"
                                                     @mouseover="$el.style.background='#f8fafc'" @mouseout="$el.style.background='white'">
                                                    <span class="ac-option__name" x-text="p.name"></span>
                                                    <span class="ac-option__stock" x-text="'dispo : '+p.available"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                    <template x-if="item.name"><span class="text-xs text-muted" x-text="'Stock dispo : ' + item.available"></span></template>
                                </td>
                                <td style="text-align:center;">
                                    <input type="number" :name="'items['+idx+'][quantity]'" x-model.number="item.quantity" min="1"
                                           class="form-control" :style="item.quantity > item.available ? 'border-color:#EF4444;color:#EF4444;' : ''" style="width:90px;text-align:center;">
                                    <div x-show="item.quantity > item.available" class="item-stock-error" x-text="'Max ' + item.available"></div>
                                </td>
                                <td><button type="button" @click="items.splice(idx,1)" class="btn btn--danger btn--sm btn--icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer-std">
                <button type="button" @click="showModal = false" class="btn btn--ghost">Annuler</button>
                <button type="button" @click="submitTransfer($el.closest('form'))" class="btn btn--primary" :disabled="items.length === 0">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script>
function tfrPage() {
    const allProducts = @json($products);
    let _key = 0;
    const CSRF = () => document.querySelector('meta[name=csrf-token]').content;

    return {
        // Listing
        rows: [], total: 0, listFrom: 0, listTo: 0, currentPage: 1, lastPage: 1, loading: true,
        search: '', filterStatus: '', filterWarehouse: '',

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
            if (this.search)          params.set('search',       this.search);
            if (this.filterStatus)    params.set('status',       this.filterStatus);
            if (this.filterWarehouse) params.set('warehouse_id', this.filterWarehouse);
            try {
                const res  = await fetch('{{ route('stock-transfers.api.list') }}?' + params, { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF() } });
                const data = await res.json();
                this.rows = data.data; this.total = data.total;
                this.listFrom = data.from; this.listTo = data.to;
                this.currentPage = data.current_page; this.lastPage = data.last_page;
            } catch(e) { console.error(e); }
            finally { this.loading = false; }
        },

        // Modal
        showModal: false, fromId: '', toId: '', items: [],

        availableForRow(idx) {
            if (!this.fromId) return [];
            const q = (this.items[idx]?._search || '').toLowerCase();
            const used = this.items.filter((_, i) => i !== idx).map(i => i.product_id).filter(Boolean);
            return allProducts.map(p => ({ ...p, available: p.stocks[this.fromId] ?? 0 }))
                .filter(p => p.available > 0 && !used.includes(p.id) && (!q || p.name.toLowerCase().includes(q)));
        },

        addRow() {
            this.items.push({ _key: ++_key, _search: '', _open: false, product_id: null, name: '', quantity: 1, available: 0 });
        },

        selectInRow(idx, p) {
            Object.assign(this.items[idx], { product_id: p.id, name: p.name, available: p.available, _search: p.name, _open: false });
        },

        submitTransfer(form) {
            if (!this.items.length) { alert('Ajoutez au moins un article.'); return; }
            if (this.items.some(i => !i.product_id)) { alert('Certaines lignes n\'ont pas de produit.'); return; }
            const bad = this.items.filter(i => i.quantity > i.available);
            if (bad.length) { alert('Stock insuffisant :\n' + bad.map(i => `• ${i.name} : demandé ${i.quantity}, dispo ${i.available}`).join('\n')); return; }
            form.submit();
        },
    };
}
</script>
@endpush
