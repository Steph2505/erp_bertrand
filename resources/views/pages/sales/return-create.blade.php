@extends('layouts.app')
@section('title', 'Nouveau retour')
@section('breadcrumb')
<a href="{{ route('sales.index') }}">Ventes</a>
<a href="{{ route('sale-returns.index') }}">Retours</a>
<span class="current">Nouveau retour</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>Enregistrer un retour</h2>
    </div>
</div>

<div class="sale-layout" x-data="returnForm()">
    <div class="card card--padded-lg">
        <form method="POST" action="{{ route('sale-returns.store') }}">
            @csrf

            <div class="form-group">
                <label>Vente concernée <span class="required">*</span></label>
                <select name="sale_id" class="form-select" @change="selectSale($event)" required>
                    <option value="">-- Sélectionner une vente --</option>
                    @foreach($sales as $s)
                        <option value="{{ $s->id }}"
                            data-total="{{ $s->total }}"
                            data-ref="{{ $s->reference }}"
                            data-customer="{{ $s->customer?->name ?? 'Comptoir' }}"
                            {{ (isset($sale) && $sale->id == $s->id) ? 'selected' : '' }}>
                            {{ $s->reference }} — {{ $s->customer?->name ?? 'Comptoir' }} — {{ \App\Helpers\FormatHelper::money($s->total) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div x-show="selectedSale" x-cloak class="return-create__sale-info">
                <strong x-text="selectedSale?.ref"></strong> —
                Client : <span x-text="selectedSale?.customer"></span><br>
                Total vente : <strong class="return-create__sale-total" x-text="fmt(selectedSale?.total)"></strong>
            </div>

            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label>Date du retour <span class="required">*</span></label>
                    <input type="date" name="return_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="form-group">
                    <label>Montant retourné ({{ $currency }}) <span class="required">*</span></label>
                    <input type="number" name="total" step="1" min="1" class="form-control"
                        :max="selectedSale?.total ?? ''"
                        x-model.number="returnAmount" required placeholder="Montant à rembourser">
                    <template x-if="selectedSale && returnAmount > selectedSale.total">
                        <span class="form-error">Ne peut pas dépasser {{ '{{ fmt(selectedSale.total) }}' }}</span>
                    </template>
                </div>
            </div>

            <div class="form-group">
                <label>Motif du retour</label>
                <textarea name="reason" class="form-control" rows="3" placeholder="Article défectueux, erreur de commande, insatisfaction..."></textarea>
            </div>

            <label class="return-create__restore-label">
                <input type="checkbox" name="restore_stock" value="1" class="return-create__restore-checkbox">
                <div>
                    <div class="return-create__restore-title">Réintégrer le stock</div>
                    <div class="return-create__restore-sub">
                        Remettre les articles en stock (uniquement pour un retour complet).
                    </div>
                </div>
            </label>

            @if($errors->any())
            <div class="alert alert--danger" style="margin-bottom:16px;">
                @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            </div>
            @endif

            <div class="return-create__actions">
                <a href="{{ route('sale-returns.index') }}" class="btn btn--ghost">Annuler</a>
                <button type="submit" class="btn btn--danger" :disabled="!selectedSale || returnAmount <= 0">
                    Enregistrer le retour
                </button>
            </div>
        </form>
    </div>

    <div class="card card--padded-lg">
        <h3 class="return-create__recap-title">Récapitulatif</h3>
        <template x-if="!selectedSale">
            <p class="return-create__recap-empty">Sélectionnez une vente pour voir le récapitulatif.</p>
        </template>
        <template x-if="selectedSale">
            <div>
                <div class="return-create__recap-row">
                    <span>Total vente</span>
                    <span x-text="fmt(selectedSale.total)"></span>
                </div>
                <div class="return-create__recap-row return-create__recap-row--danger">
                    <span>Montant retourné</span>
                    <span x-text="fmt(returnAmount || 0)"></span>
                </div>
                <div class="return-create__recap-total">
                    <span>Solde restant</span>
                    <span x-text="fmt(Math.max(0, selectedSale.total - (returnAmount || 0)))"></span>
                </div>
            </div>
        </template>
    </div>
</div>
@endsection

@push('scripts')
<script>
function returnForm() {
    return {
        selectedSale: @if(isset($sale)) { total: {{ $sale->total }}, ref: '{{ $sale->reference }}', customer: '{{ $sale->customer?->name ?? 'Comptoir' }}' } @else null @endif,
        returnAmount: 0,

        selectSale(e) {
            const opt = e.target.selectedOptions[0];
            if (!opt.value) { this.selectedSale = null; return; }
            this.selectedSale = {
                total:    parseFloat(opt.dataset.total),
                ref:      opt.dataset.ref,
                customer: opt.dataset.customer,
            };
            this.returnAmount = this.selectedSale.total;
        },

        fmt(v) { return new Intl.NumberFormat('fr-FR').format(Math.round(v || 0)) + ' ' + window.CURRENCY; },
    };
}
</script>
@endpush
