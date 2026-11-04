@extends('layouts.app')
@section('title', 'Nouveau retour')
@section('breadcrumb')
<a title="Aller à : Historique POS" href="{{ route('pos.list') }}">Historique POS</a>
<a title="Aller à : Retours" href="{{ route('pos.returns.index') }}">Retours</a>
<span class="current">Nouveau retour</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>Enregistrer un retour</h2>
    </div>
</div>

<div class="card card--padded-lg" style="margin-bottom:20px;">
    <form method="GET" action="{{ route('pos.returns.create') }}"
        x-data="saleSearch(@js($sales), @js($sale?->id))" x-init="init()">
        <div class="form-group">
            <label for="sale_id">Vente concernée <span class="required">*</span></label>
            <input type="hidden" name="sale_id" x-model="selectedId" required>
            <div class="autocomplete-wrap" @click.outside="open=false">
                <input id="sale_id_search" type="search" class="form-control"
                    x-model="search" @focus="open=true; search=''" @input="open=true"
                    @keydown.enter.prevent
                    placeholder="Rechercher par référence, client ou montant..."
                    autocomplete="off" aria-label="Rechercher une vente">
                <div x-show="open" x-transition class="autocomplete-dropdown" x-cloak>
                    <template x-for="saleOption in filteredSales()" :key="saleOption.id">
                        <div class="autocomplete-dropdown__item" @mousedown.prevent="select(saleOption)">
                            <strong x-text="saleOption.reference"></strong>
                            <span> — </span><span x-text="saleOption.customer"></span>
                            <span> — </span><span x-text="fmt(saleOption.total)"></span>
                        </div>
                    </template>
                    <template x-if="filteredSales().length === 0">
                        <div class="autocomplete-dropdown__empty">Aucune vente trouvée</div>
                    </template>
                </div>
            </div>
        </div>
    </form>
</div>

@if($sale)
<div class="sale-layout sale-return-layout" x-data="returnForm(@js($saleLines))">
    <div class="card card--padded-lg">
        @if($hasLegacyReturns)
        <div class="alert alert--danger" style="margin-bottom:16px;">
            Cette vente a un ancien retour sans détail par article. Impossible d’enregistrer un nouveau retour automatiquement.
        </div>
        @endif

        <div class="return-create__sale-info" style="margin-bottom:20px;">
            <strong>{{ $sale->reference }}</strong> —
            Client : {{ $sale->customer?->name ?? 'Comptoir' }}<br>
            Total de la vente : <strong class="return-create__sale-total">{{ \App\Helpers\FormatHelper::money($sale->total) }}</strong>
        </div>

        <form method="POST" action="{{ route('pos.returns.store') }}">
            @csrf
            <input type="hidden" name="sale_id" value="{{ $sale->id }}">

            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Article</th>
                            <th>Vendu</th>
                            <th>Déjà retourné</th>
                            <th>À retourner</th>
                            <th>État / stock</th>
                            <th>Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="line in lines" :key="line.id">
                            <tr>
                                <td>
                                    <strong x-text="line.item_name"></strong>
                                    <small x-show="line.item_type === 'pack'" style="display:block;">Pack (retour par pack entier)</small>
                                </td>
                                <td x-text="line.quantity"></td>
                                <td x-text="line.returned_quantity"></td>
                                <td>
                                    <input type="number" class="form-control"
                                        x-bind:name="'items[' + line.id + '][quantity]'"
                                        x-model.number="line.return_quantity"
                                        min="0" x-bind:max="line.remaining" step="1"
                                        x-bind:disabled="line.remaining === 0" style="max-width:100px;">
                                    <small x-text="'Maximum : ' + line.remaining"></small>
                                </td>
                                <td>
                                    <select class="form-select"
                                        x-bind:name="'items[' + line.id + '][stock_condition]'"
                                        x-model="line.stock_condition"
                                        x-bind:disabled="line.remaining === 0">
                                        <option value="resellable">Bon état — réintégrer</option>
                                        <option value="damaged">Défectueux — ne pas réintégrer</option>
                                    </select>
                                </td>
                                <td x-text="fmt(lineRefund(line))"></td>
                            </tr>
                        </template>
                        <tr x-show="lines.length === 0">
                            <td colspan="6">Aucun article dans cette vente.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="form-grid form-grid--2" style="margin-top:20px;">
                <div class="form-group">
                    <label for="return_date">Date du retour <span class="required">*</span></label>
                    <input id="return_date" type="date" name="return_date" class="form-control" value="{{ old('return_date', date('Y-m-d')) }}" required>
                </div>
                <div class="form-group">
                    <label for="payment_method">Mode de remboursement <span class="required">*</span></label>
                    <select id="payment_method" name="payment_method" class="form-select" required>
                        <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Espèces</option>
                        <option value="mobile_money" @selected(old('payment_method') === 'mobile_money')>Mobile Money</option>
                        <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>Virement bancaire</option>
                        <option value="check" @selected(old('payment_method') === 'check')>Chèque</option>
                        <option value="card" @selected(old('payment_method') === 'card')>Carte</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="payment_account_id">Compte de sortie</label>
                <select id="payment_account_id" name="payment_account_id" class="form-select">
                    <option value="">Choisir automatiquement selon le mode de remboursement</option>
                    @foreach($paymentAccounts as $account)
                        <option value="{{ $account->id }}" @selected((string) old('payment_account_id') === (string) $account->id)>
                            {{ $account->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="reason">Motif du retour</label>
                <textarea id="reason" name="reason" class="form-control" rows="3" maxlength="500">{{ old('reason') }}</textarea>
            </div>

            @if($errors->any())
            <div class="alert alert--danger" style="margin-bottom:16px;">
                @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            </div>
            @endif
            @if(session('error'))
            <div class="alert alert--danger" style="margin-bottom:16px;">{{ session('error') }}</div>
            @endif

            <div class="return-create__recap-total" style="margin:20px 0;">
                <span>Montant calculé du remboursement</span>
                <strong x-text="fmt(totalRefund())"></strong>
            </div>

            <div class="return-create__actions">
                <a title="Annuler et fermer sans enregistrer" href="{{ route('pos.returns.index') }}" class="btn btn--light">Annuler</a>
                <button title="Enregistrer le retour" type="submit" class="btn btn--danger" x-bind:disabled="!hasItems() || {{ $hasLegacyReturns ? 'true' : 'false' }}">
                    Enregistrer le retour
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
function saleSearch(sales, selectedId) {
    return {
        sales,
        selectedId: selectedId || '',
        search: '',
        open: false,
        init() {
            const selected = this.sales.find(sale => sale.id === Number(this.selectedId));
            if (selected) this.search = this.labelFor(selected);
        },
        labelFor(sale) {
            return `${sale.reference} — ${sale.customer} — ${this.fmt(sale.total)}`;
        },
        filteredSales() {
            const query = this.search.trim().toLocaleLowerCase();
            if (!query) return this.sales;
            return this.sales.filter(sale =>
                `${sale.reference} ${sale.customer} ${sale.total}`.toLocaleLowerCase().includes(query)
            );
        },
        select(sale) {
            this.selectedId = sale.id;
            this.search = this.labelFor(sale);
            this.open = false;
            this.$el.submit();
        },
        fmt(value) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(value || 0)) + ' ' + window.CURRENCY;
        },
    };
}

function returnForm(lines) {
    return {
        lines: lines.map(line => ({
            ...line,
            remaining: Math.max(0, line.quantity - line.returned_quantity),
            return_quantity: 0,
            stock_condition: 'resellable',
        })),
        lineRefund(line) {
            const cumulativeQuantity = line.returned_quantity + Math.max(0, Number(line.return_quantity || 0));
            const cumulativeTotal = Math.round((line.subtotal * cumulativeQuantity / line.quantity) * 100) / 100;
            return Math.max(0, Math.round((cumulativeTotal - line.returned_total) * 100) / 100);
        },
        totalRefund() {
            return this.lines.reduce((total, line) => total + this.lineRefund(line), 0);
        },
        hasItems() {
            return this.lines.some(line => line.return_quantity > 0 && line.return_quantity <= line.remaining);
        },
        fmt(value) {
            return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value || 0)
                + ' ' + window.CURRENCY;
        },
    };
}
</script>
@endpush
