@extends('layouts.app')
@section('title', 'Produits')
@section('breadcrumb')<span class="current">Produits</span>@endsection

@section('content')
<div x-data="productList()" x-init="fetch()">

<div class="page-header">
    <div class="page-header__title">
        <h2>Produits</h2>
        <p x-text="total + ' produit(s) au total'">— produit(s) au total</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('units.index') }}" class="btn btn--ghost">Unités</a>
        <a href="{{ route('categories.index') }}" class="btn btn--ghost">Catégories</a>
        <a href="{{ route('products.create') }}" class="btn btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Ajouter un produit
        </a>
    </div>
</div>

<div class="table-wrapper">

    {{-- Filtres (sans bouton submit) --}}
    <div class="table-wrapper__header" style="flex-wrap:wrap;gap:10px;">
        <div class="filters-bar" style="flex:1;flex-wrap:wrap;">

            {{-- Recherche --}}
            <div class="input-group" style="flex:1;min-width:200px;">
                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input type="text" x-model="filters.search" @input.debounce.400ms="reset()"
                       class="form-control" placeholder="Rechercher nom, code-barres...">
            </div>

            {{-- Catégorie --}}
            <select x-model="filters.category_id" @change="reset()" class="form-select">
                <option value="">Toutes catégories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>

            {{-- Statut --}}
            <select x-model="filters.is_active" @change="reset()" class="form-select">
                <option value="">Tous statuts</option>
                <option value="1">Actif</option>
                <option value="0">Inactif</option>
            </select>

            {{-- Stock faible --}}
            <label class="filter-checkbox" style="cursor:pointer;">
                <input type="checkbox" x-model="filters.low_stock" @change="reset()"> Stock faible
            </label>

            {{-- Réinitialiser --}}
            <button x-show="hasFilters" @click="clearFilters()" class="btn btn--ghost btn--sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                Effacer
            </button>
        </div>

        {{-- Loader --}}
        <div x-show="loading" style="display:flex;align-items:center;gap:6px;color:#64748B;font-size:13px;">
            <svg style="width:16px;height:16px;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".3"/>
                <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
            </svg>
            Chargement...
        </div>
    </div>

    {{-- Tableau --}}
    <div style="position:relative;">

        {{-- Overlay loader --}}
        <div x-show="loading && products.length > 0"
             style="position:absolute;inset:0;background:rgba(255,255,255,0.65);z-index:10;display:flex;align-items:center;justify-content:center;">
            <svg style="width:32px;height:32px;color:#1749B3;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/>
                <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
            </svg>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Catégorie</th>
                    <th>Prix achat</th>
                    <th>Prix vente</th>
                    <th>Packable</th>
                    <th>Statut</th>
                    <th class="th-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                {{-- Premier chargement --}}
                <template x-if="loading && products.length === 0">
                    <tr>
                        <td colspan="7" style="text-align:center;padding:48px;color:#64748B;">
                            <svg style="width:24px;height:24px;color:#1749B3;animation:spin 1s linear infinite;margin:0 auto 12px;" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/>
                                <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                            </svg>
                            <div>Chargement des produits...</div>
                        </td>
                    </tr>
                </template>

                {{-- Aucun résultat --}}
                <template x-if="!loading && products.length === 0">
                    <tr>
                        <td colspan="7" class="table-empty-cell">
                            Aucun produit trouvé.
                            <a href="{{ route('products.create') }}" style="color:#1749B3;">Ajouter le premier</a>
                        </td>
                    </tr>
                </template>

                {{-- Lignes produits --}}
                <template x-for="product in products" :key="product.id">
                    <tr>
                        <td>
                            <div class="product-cell">
                                <template x-if="product.image_url">
                                    <img :src="product.image_url" class="data-table__img">
                                </template>
                                <template x-if="!product.image_url">
                                    <div class="data-table__img data-table__img-placeholder">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                                    </div>
                                </template>
                                <div class="product-cell__info">
                                    <a :href="product.show_url" class="product-cell__name" x-text="product.name"></a>
                                    <div class="product-cell__barcode" x-show="product.barcode" x-text="product.barcode"></div>
                                </div>
                            </div>
                        </td>
                        <td x-text="product.category"></td>
                        <td x-text="product.buying_price"></td>
                        <td x-text="product.selling_price"></td>
                        <td>
                            <template x-if="product.can_be_packed">
                                <span class="badge badge--green" x-text="'Pack ×' + product.pack_quantity"></span>
                            </template>
                            <template x-if="!product.can_be_packed">
                                <span class="badge badge--gray">—</span>
                            </template>
                        </td>
                        <td>
                            <span class="badge" :class="product.is_active ? 'badge--green' : 'badge--gray'"
                                  x-text="product.is_active ? 'Actif' : 'Inactif'"></span>
                        </td>
                        <td>
                            <div class="data-table__actions">
                                <a :href="product.show_url" class="btn btn--ghost btn--icon" title="Voir">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                </a>
                                <a :href="product.edit_url" class="btn btn--ghost btn--icon" title="Modifier">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                </a>
                                <button @click="deleteProduct(product)" class="btn btn--ghost btn--icon btn-table-delete" title="Supprimer">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="table-wrapper__footer" x-show="lastPage > 0">
        <span x-text="from + '–' + to + ' sur ' + total" class="text-muted text-sm"></span>

        <div style="display:flex;gap:4px;align-items:center;">
            {{-- Précédent --}}
            <button @click="goTo(currentPage - 1)" :disabled="currentPage <= 1 || loading"
                    class="btn btn--ghost btn--sm btn--icon" title="Page précédente">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            </button>

            {{-- Pages --}}
            <template x-for="p in pages" :key="p">
                <button @click="p !== '…' && goTo(p)"
                        class="btn btn--sm"
                        :class="p === currentPage ? 'btn--primary' : (p === '…' ? 'btn--ghost' : 'btn--ghost')"
                        :disabled="p === '…' || loading"
                        x-text="p"
                        style="min-width:36px;justify-content:center;"></button>
            </template>

            {{-- Suivant --}}
            <button @click="goTo(currentPage + 1)" :disabled="currentPage >= lastPage || loading"
                    class="btn btn--ghost btn--sm btn--icon" title="Page suivante">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </button>
        </div>
    </div>
</div>

</div>{{-- /x-data --}}

@push('scripts')
<script>
function productList() {
    return {
        products:    [],
        total:       0,
        from:        0,
        to:          0,
        currentPage: 1,
        lastPage:    1,
        loading:     true,

        filters: {
            search:      '',
            category_id: '',
            is_active:   '',
            low_stock:   false,
        },

        get hasFilters() {
            return this.filters.search || this.filters.category_id || this.filters.is_active || this.filters.low_stock;
        },

        get pages() {
            const total = this.lastPage;
            const cur   = this.currentPage;
            if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
            const pages = [];
            pages.push(1);
            if (cur > 3)         pages.push('…');
            for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i);
            if (cur < total - 2) pages.push('…');
            pages.push(total);
            return pages;
        },

        reset() {
            this.currentPage = 1;
            this.fetch();
        },

        goTo(page) {
            if (page < 1 || page > this.lastPage) return;
            this.currentPage = page;
            this.fetch();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        clearFilters() {
            this.filters = { search: '', category_id: '', is_active: '', low_stock: false };
            this.reset();
        },

        async fetch() {
            this.loading = true;
            const params = new URLSearchParams({
                page: this.currentPage,
                ...(this.filters.search      && { search:      this.filters.search }),
                ...(this.filters.category_id && { category_id: this.filters.category_id }),
                ...(this.filters.is_active   && { is_active:   this.filters.is_active }),
                ...(this.filters.low_stock   && { low_stock:   1 }),
            });
            try {
                const res  = await fetch('{{ route('products.api.list') }}?' + params, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                });
                const data = await res.json();
                this.products    = data.data;
                this.total       = data.total;
                this.from        = data.from;
                this.to          = data.to;
                this.currentPage = data.current_page;
                this.lastPage    = data.last_page;
            } catch(e) {
                console.error('Erreur chargement produits:', e);
            } finally {
                this.loading = false;
            }
        },

        async deleteProduct(product) {
            if (!confirm('Supprimer le produit « ' + product.name + ' » ?')) return;
            try {
                await fetch(product.delete_url, {
                    method:  'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                });
                this.products = this.products.filter(p => p.id !== product.id);
                this.total--;
                if (this.products.length === 0 && this.currentPage > 1) {
                    this.goTo(this.currentPage - 1);
                }
                window.toast('Produit supprimé avec succès.', 'success');
            } catch(e) {
                window.toast('Erreur lors de la suppression.', 'error');
            }
        },
    };
}
</script>

<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
@endpush
@endsection
