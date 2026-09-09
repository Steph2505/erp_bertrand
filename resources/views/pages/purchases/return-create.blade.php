@extends('layouts.app')
@section('title', "Nouveau retour d'achat")
@section('breadcrumb')
    <a href="{{ route('purchases.index') }}">Achats</a>
    <span class="sep">/</span>
    <a href="{{ route('purchase-returns.index') }}">Retours</a>
    <span class="sep">/</span><span class="current">Nouveau retour</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title"><h2>Enregistrer un retour d'achat</h2></div>
</div>

<div class="purchase-return-create__layout" x-data="returnForm()">

    <div class="card purchase-card--lg">
        <form method="POST" action="{{ route('purchase-returns.store') }}">
            @csrf

            <div class="form-group" @click.outside="purchaseOpen=false; purchaseSearch=selectedPurchase?.label??''">
                <label>Achat concerné <span class="required">*</span></label>
                <div class="autocomplete-wrap">
                    <input type="text" x-model="purchaseSearch"
                           @focus="purchaseSearch=''; purchaseOpen=true"
                           @input="purchaseOpen=true"
                           placeholder="Référence ou fournisseur..."
                           class="form-control" autocomplete="off">
                    <input type="hidden" name="purchase_id" :value="selectedPurchase?.id??''">
                    <div x-show="purchaseOpen" x-transition class="autocomplete-dropdown autocomplete-dropdown--tall">
                        <template x-if="filteredPurchases.length === 0">
                            <div class="autocomplete-dropdown__empty--lg">Aucun achat confirmé trouvé</div>
                        </template>
                        <template x-for="p in filteredPurchases" :key="p.id">
                            <div @mousedown.prevent="selectPurchase(p)"
                                 class="autocomplete-dropdown__item--separator"
                                 :style="selectedPurchase?.id===p.id?'background:#eff6ff;':''"
                                 @mouseover="$el.style.background='#f8fafc'"
                                 @mouseout="$el.style.background=selectedPurchase?.id===p.id?'#eff6ff':'white'">
                                <div class="autocomplete-dropdown__item-header">
                                    <span x-text="p.ref" class="autocomplete-dropdown__item-ref"></span>
                                    <span x-text="fmt(p.total)" class="autocomplete-dropdown__item-total"></span>
                                </div>
                                <div class="autocomplete-dropdown__item-meta">
                                    <span x-text="p.supplier"></span>
                                    <span x-text="p.date" class="autocomplete-dropdown__item-date"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div x-show="selectedPurchase" x-cloak class="purchase-return-create__selected-recap">
                <div class="purchase-return-create__recap-row">
                    <div>
                        <strong x-text="selectedPurchase?.ref"></strong>
                        <span x-text="'Fournisseur : ' + (selectedPurchase?.supplier ?? '—')" class="purchase-return-create__recap-supplier"></span>
                    </div>
                    <strong x-text="fmt(selectedPurchase?.total ?? 0)" class="purchase-return-create__recap-total"></strong>
                </div>
            </div>

            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label>Date du retour <span class="required">*</span></label>
                    <input type="date" name="return_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="form-group">
                    <label>Montant retourné ({{ $currency }}) <span class="required">*</span></label>
                    <input type="number" name="total" step="1" min="1" class="form-control"
                           :max="selectedPurchase?.total ?? ''"
                           x-model.number="returnAmount"
                           required placeholder="Montant à rembourser">
                    <template x-if="selectedPurchase && returnAmount > selectedPurchase.total">
                        <span class="purchase-return-create__amount-error">
                            Ne peut pas dépasser <strong x-text="fmt(selectedPurchase.total)"></strong>
                        </span>
                    </template>
                </div>
            </div>

            <div class="form-group">
                <label>Motif du retour</label>
                <textarea name="reason" class="form-textarea" rows="3"
                          placeholder="Article défectueux, erreur de commande, excédent..."></textarea>
            </div>

            @if($errors->any())
            <div class="alert alert--danger" style="margin-bottom:16px;">
                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
            @endif

            <div class="purchase-return-create__actions">
                <a href="{{ route('purchase-returns.index') }}" class="btn btn--ghost">Annuler</a>
                <button type="submit" class="btn btn--danger"
                        :disabled="!selectedPurchase || returnAmount <= 0 || returnAmount > (selectedPurchase?.total ?? 0)">
                    Enregistrer le retour
                </button>
            </div>
        </form>
    </div>

    <div class="purchase-return-create__sidebar">
        <div class="card purchase-card">
            <h3 class="purchase-summary__title--sm">Récapitulatif</h3>
            <template x-if="!selectedPurchase">
                <p class="return-summary__empty">Sélectionnez un achat pour voir le récapitulatif.</p>
            </template>
            <template x-if="selectedPurchase">
                <div class="return-summary__rows">
                    <div class="return-summary__row">
                        <span class="return-summary__label">Total achat</span>
                        <strong x-text="fmt(selectedPurchase.total)"></strong>
                    </div>
                    <div class="return-summary__row--returned">
                        <span>Montant retourné</span>
                        <strong x-text="fmt(returnAmount || 0)"></strong>
                    </div>
                    <div class="return-summary__divider"></div>
                    <div class="return-summary__row--balance">
                        <span>Solde restant</span>
                        <span x-text="fmt(Math.max(0, selectedPurchase.total - (returnAmount || 0)))"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function returnForm() {
    const purchases = @json($purchases);
    @if(isset($purchase))
    const preselected = purchases.find(p => p.id === {{ $purchase->id }}) ?? null;
    @else
    const preselected = null;
    @endif

    return {
        purchases,
        selectedPurchase: preselected,
        purchaseSearch:   preselected?.label ?? '',
        purchaseOpen:     false,
        returnAmount:     preselected ? preselected.total : 0,

        get filteredPurchases() {
            const q = this.purchaseSearch.toLowerCase();
            return this.purchases.filter(p =>
                p.ref.toLowerCase().includes(q) || p.supplier.toLowerCase().includes(q)
            );
        },

        selectPurchase(p) {
            this.selectedPurchase = p;
            this.purchaseSearch   = p.label;
            this.purchaseOpen     = false;
            this.returnAmount     = p.total;
        },

        fmt(v) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(v || 0)) + ' ' + window.CURRENCY;
        },
    };
}
</script>
@endpush
