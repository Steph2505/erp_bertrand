@extends('layouts.app')
@section('title', 'État de stock')
@section('breadcrumb')<span class="current">État de stock</span>@endsection

@section('content')
<div x-data="stockStatusPage()" x-init="fetch()">

<div class="page-header">
    <div class="page-header__title">
        <h2>État de stock</h2>
        <p x-text="total + ' article(s)'">—</p>
    </div>
</div>

<div class="table-wrapper">
    <div class="table-wrapper__header" style="flex-wrap:wrap;gap:10px;">
        <div class="filters-bar" style="flex:1;flex-wrap:wrap;">
            <div class="input-group" style="flex:1;min-width:180px;">
                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                <input type="text" x-model="filters.search" @input.debounce.400ms="reset()" class="form-control" placeholder="Rechercher un article...">
            </div>
            <select x-model="filters.warehouse_id" @change="reset()" class="form-select">
                <option value="">Tous les magasins</option>
                @foreach($warehouses as $wh)
                    <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                @endforeach
            </select>
            <select x-model="filters.category_id" @change="reset()" class="form-select">
                <option value="">Toutes catégories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            <select x-model="filters.status" @change="reset()" class="form-select">
                <option value="">Tous statuts</option>
                <option value="low">Stock faible</option>
                <option value="out">Rupture</option>
            </select>
            <button x-show="hasFilters" @click="clearFilters()" class="btn btn--ghost btn--sm">✕ Effacer</button>
        </div>
        <span x-show="loading" class="text-muted text-sm">Chargement...</span>
    </div>

    <div style="position:relative;">
        <div x-show="loading && rows.length > 0" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
            <svg style="width:28px;height:28px;color:#4CBB17;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Article</th>
                    <th>Catégorie</th>
                    <th x-text="extra.warehouse_filter ? 'Magasin' : 'Magasins / Stock'"></th>
                    <th class="th-right">Stock min.</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <template x-if="loading && rows.length === 0">
                    <tr><td colspan="5" style="text-align:center;padding:40px;color:#64748B;">Chargement...</td></tr>
                </template>
                <template x-if="!loading && rows.length === 0">
                    <tr><td colspan="5" class="table-empty-cell">Aucun article trouvé</td></tr>
                </template>
                <template x-for="p in rows" :key="p.id">
                    <tr>
                        <td><a :href="p.show_url" class="product-cell__name" x-text="p.name"></a></td>
                        <td x-text="p.category"></td>
                        <td>
                            <template x-if="extra.warehouse_filter">
                                <span :class="p.is_out ? 'field-stock--low' : (p.is_low ? 'field-stock--low' : 'field-stock--ok')"
                                      x-text="p.qty + ' ' + p.unit"></span>
                            </template>
                            <template x-if="!extra.warehouse_filter">
                                <div>
                                    <span :class="p.is_out ? 'field-stock--low' : (p.is_low ? 'field-stock--low' : 'field-stock--ok')"
                                          x-text="p.qty + ' ' + p.unit + ' total'"></span>
                                    <template x-if="p.stocks && p.stocks.length > 0">
                                        <div class="text-xs text-muted" style="margin-top:2px;">
                                            <template x-for="s in p.stocks" :key="s.warehouse">
                                                <span style="margin-right:8px;" x-text="s.warehouse + ' : ' + s.quantity"></span>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </td>
                        <td class="th-right" x-text="p.min_qty + ' ' + p.unit"></td>
                        <td>
                            <template x-if="p.is_out"><span class="badge badge--red">Rupture</span></template>
                            <template x-if="p.is_low && !p.is_out"><span class="badge badge--yellow">Stock faible</span></template>
                            <template x-if="!p.is_low && !p.is_out"><span class="badge badge--green">OK</span></template>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div class="table-wrapper__footer">
        <span x-text="from + '–' + to + ' sur ' + total" class="text-muted text-sm"></span>
        <div style="display:flex;gap:4px;" x-show="lastPage > 1">
            <button @click="goTo(currentPage-1)" :disabled="currentPage<=1||loading" class="btn btn--ghost btn--sm btn--icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg></button>
            <template x-for="p in pages" :key="p"><button @click="p!=='…'&&goTo(p)" class="btn btn--sm" :class="p===currentPage?'btn--primary':'btn--ghost'" :disabled="p==='…'||loading" x-text="p" style="min-width:34px;justify-content:center;"></button></template>
            <button @click="goTo(currentPage+1)" :disabled="currentPage>=lastPage||loading" class="btn btn--ghost btn--sm btn--icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg></button>
        </div>
    </div>
</div>

</div>

@push('scripts')
<script>
function stockStatusPage() {
    return {
        ...listPage('{{ route('stock-status.api.list') }}'),
        filters: { search: '', warehouse_id: '', category_id: '', status: '' },
    };
}
</script>
@endpush

@include('components.list-page-script')
@endsection
