@extends('layouts.app')
@section('title', 'Achats')
@section('breadcrumb')<span class="current">Achats</span>@endsection

@section('content')
<div x-data="{...listPage('{{ route('purchases.api.list') }}'), ...purchasePayExtra()}" x-init="fetch()">

<div class="page-header">
    <div class="page-header__title">
        <h2>Achats</h2>
        <p x-text="total + ' achat(s)'">—</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('purchases.create') }}" class="btn btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nouvel achat
        </a>
    </div>
</div>

<div class="table-wrapper purchase-filters">
    <div class="purchase-filters__grid">
        <div class="form-group purchase-filters__group">
            <label>Recherche</label>
            <input type="text" x-model="filters.search" @input.debounce.400ms="reset()" class="form-control" placeholder="Référence...">
        </div>
        <div class="form-group purchase-filters__group">
            <label>Fournisseur</label>
            <select x-model="filters.supplier_id" @change="reset()" class="form-select">
                <option value="">Tous</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group purchase-filters__group">
            <label>Statut</label>
            <select x-model="filters.status" @change="reset()" class="form-select">
                <option value="">Tous</option>
                <option value="draft">Brouillon</option>
                <option value="confirmed">Confirmé</option>
            </select>
        </div>
        <div style="display:flex;gap:8px;align-items:flex-end;">
            <button x-show="hasFilters" @click="clearFilters()" class="btn btn--ghost btn--sm">✕ Effacer</button>
            <span x-show="loading" class="text-muted text-sm">Chargement...</span>
        </div>
    </div>
</div>

<div class="table-wrapper">
    <div style="position:relative;">
        <div x-show="loading && rows.length > 0" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
            <svg style="width:28px;height:28px;color:#1749B3;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>Référence</th><th>Fournisseur</th><th>Date</th>
                <th>Statut</th><th>Paiement</th><th>Total</th><th class="th-right">Actions</th>
            </tr></thead>
            <tbody>
                <template x-if="loading && rows.length === 0">
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:#64748B;">Chargement...</td></tr>
                </template>
                <template x-if="!loading && rows.length === 0">
                    <tr><td colspan="7" class="purchase-index__empty">Aucun achat trouvé</td></tr>
                </template>
                <template x-for="p in rows" :key="p.id">
                    <tr>
                        <td><a :href="p.show_url" class="purchase-index__ref-link" x-text="p.reference"></a></td>
                        <td x-text="p.supplier"></td>
                        <td x-text="p.purchase_date"></td>
                        <td><span x-html="p.status_badge"></span></td>
                        <td><span x-html="p.pay_badge"></span></td>
                        <td><strong x-text="p.total"></strong></td>
                        <td>
                            <div class="data-table__actions">
                                <a :href="p.show_url" class="btn btn--ghost btn--sm btn--icon" title="Voir">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                </a>
                                <template x-if="p.status === 'draft'">
                                    <a :href="p.edit_url" class="btn btn--ghost btn--sm btn--icon" title="Modifier">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                                    </a>
                                </template>
                                <template x-if="p.status === 'confirmed' && p.payment_status !== 'paid'">
                                    <button type="button" @click="openPay(p)" class="btn btn--pay-filled btn--sm btn--icon" title="Payer">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3M3.75 4.5h16.5a1.5 1.5 0 0 1 1.5 1.5v12a1.5 1.5 0 0 1-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5V6a1.5 1.5 0 0 1 1.5-1.5Z"/></svg>
                                    </button>
                                </template>
                                <a :href="p.print_url" target="_blank" class="btn btn--ghost btn--sm btn--icon" title="Imprimer">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659"/></svg>
                                </a>
                            </div>
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

{{-- Modale paiement --}}
<div class="modal-overlay" x-show="payOpen" x-cloak @click.self="payOpen=false" x-transition>
    <div class="modal modal--sm">
        <div class="modal__header">
            <h3>Régler cet achat</h3>
            <button class="modal__close" @click="payOpen=false">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal__body">
            <div x-show="paySuccess" class="alert alert--success modal-alert" x-text="paySuccess"></div>
            <div x-show="payError" class="alert alert--danger modal-alert" x-text="payError"></div>
            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label>Montant ({{ $currency }}) <span class="required">*</span></label>
                    <input type="number" x-model="payAmount" min="1" step="1" class="form-control" :class="{'form-control--error': payErrors.amount}"
                           :placeholder="payPurchase?.amount_due" required>
                    <span class="form-error" x-show="payErrors.amount" x-text="payErrors.amount"></span>
                </div>
                <div class="form-group">
                    <label>Compte débité <span class="required">*</span></label>
                    <select x-model="payAccountId" class="form-select" :class="{'form-control--error': payErrors.accountId}">
                        @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                        @endforeach
                    </select>
                    <span class="form-error" x-show="payErrors.accountId" x-text="payErrors.accountId"></span>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" @click="payOpen=false" class="btn btn--light">Annuler</button>
                <button type="button" @click="submitPay()" :disabled="payLoading" class="btn btn--pay-filled">
                    <span x-show="!payLoading">Valider</span>
                    <span x-show="payLoading">...</span>
                </button>
            </div>
        </div>
    </div>
</div>

</div>
@include('components.list-page-script')
@push('scripts')
<script>
function purchasePayExtra() {
    return {
        payOpen: false, payLoading: false, paySuccess: '', payError: '',
        payPurchase: null, payAmount: '', payMode: 'cash', payAccountId: '{{ $accounts->first()->id ?? '' }}',
        payErrors: { amount: '', accountId: '' },

        openPay(p) {
            this.payPurchase = p;
            this.payAmount   = p.amount_due;
            this.payErrors   = { amount: '', accountId: '' };
            this.paySuccess  = '';
            this.payError    = '';
            this.payOpen     = true;
        },

        async submitPay() {
            this.payError = ''; this.paySuccess = '';
            this.payErrors = { amount: '', accountId: '' };
            if (!this.payAmount)    this.payErrors.amount    = 'Le montant est obligatoire.';
            if (!this.payAccountId) this.payErrors.accountId = 'Le compte de paiement est obligatoire.';
            if (this.payErrors.amount || this.payErrors.accountId) return;
            const ok = await window.confirmDialog('Enregistrer ce paiement de ' + this.payAmount + ' ' + window.CURRENCY + ' ?', { confirmLabel: 'Enregistrer' });
            if (!ok) return;
            this.payLoading = true;
            try {
                const res = await fetch(this.payPurchase.pay_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        amount: this.payAmount,
                        payment_account_id: this.payAccountId || null,
                    }),
                });
                const data = await res.json();
                this.payLoading = false;
                if (data.success) {
                    this.paySuccess = 'Paiement enregistré.';
                    window.toast('Paiement enregistré avec succès.', 'success');
                    this.payOpen = false;
                    this.fetch();
                } else if (data.errors) {
                    this.payErrors.amount    = data.errors.amount?.[0]           ?? '';
                    this.payErrors.accountId = data.errors.payment_account_id?.[0] ?? '';
                    this.payError = data.message ?? 'Veuillez corriger les erreurs.';
                    window.toast(this.payError, 'error');
                } else {
                    this.payError = data.message ?? 'Erreur.';
                    window.toast(this.payError, 'error');
                }
            } catch (e) {
                this.payLoading = false;
                this.payError = 'Erreur réseau.';
                window.toast('Erreur réseau.', 'error');
            }
        },
    };
}
</script>
@endpush
@endsection
