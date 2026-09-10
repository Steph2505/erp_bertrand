@extends('layouts.app')
@section('title', 'Achat ' . $purchase->reference)
@section('breadcrumb')
    <a href="{{ route('purchases.index') }}">Achats</a>
    <span class="sep">/</span>
    <span class="current">{{ $purchase->reference }}</span>
@endsection

@section('content')
<div x-data="payForm('{{ route('purchases.pay', $purchase) }}')" @keydown.escape.window="open=false">
<div class="page-header">
    <div class="page-header__title">
        <h2>{{ $purchase->reference }}</h2>
        <p>Achat du {{ \App\Helpers\FormatHelper::date($purchase->purchase_date) }}</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('print.purchase', $purchase) }}" target="_blank" class="btn btn--light">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659"/></svg>
            Imprimer le bon
        </a>
        <a href="{{ route('purchases.index') }}" class="btn btn--ghost">Retour</a>
    </div>
</div>

<div class="purchase-show-layout">

    <div class="purchase-show-layout__main">

        <div class="table-wrapper">
            <div class="table-wrapper__header"><strong>Articles reçus</strong></div>
            <table class="data-table">
                <thead><tr>
                    <th>Produit</th>
                    <th class="purchase-show__th-qty">Qté</th>
                    <th>Prix unit.</th>
                    <th class="purchase-show__th-subtotal">Sous-total</th>
                </tr></thead>
                <tbody>
                    @foreach($purchase->items as $item)
                    <tr>
                        <td class="purchase-show__item-name">{{ $item->item_name }}</td>
                        <td class="purchase-show__item-qty">{{ $item->quantity }}</td>
                        <td>{{ \App\Helpers\FormatHelper::money($item->unit_price) }}</td>
                        <td class="purchase-show__item-subtotal">{{ \App\Helpers\FormatHelper::money($item->subtotal) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="tfoot-label">Total</td>
                        <td class="tfoot-total">{{ \App\Helpers\FormatHelper::money($purchase->total) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if($purchase->returns->count() > 0)
        <div class="table-wrapper">
            <div class="table-wrapper__header">
                <strong class="returns-table__header-title">Retours</strong>
                <span class="returns-table__count">{{ $purchase->returns->count() }} retour(s)</span>
            </div>
            <table class="data-table">
                <thead><tr>
                    <th>Référence</th>
                    <th>Date</th>
                    <th class="th-right">Montant retourné</th>
                    <th>Motif</th>
                </tr></thead>
                <tbody>
                    @foreach($purchase->returns as $ret)
                    <tr>
                        <td class="returns-table__ref">{{ $ret->reference }}</td>
                        <td>{{ \App\Helpers\FormatHelper::date($ret->return_date) }}</td>
                        <td class="returns-table__amount">{{ \App\Helpers\FormatHelper::money($ret->total) }}</td>
                        <td class="returns-table__reason">{{ $ret->reason ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

    </div>

    <div class="purchase-show-layout__sidebar">

        <div class="card purchase-card">
            <h3 class="purchase-summary__title--sm">Informations</h3>
            <div class="purchase-info">
                <div class="purchase-info__row">
                    <span class="purchase-info__label">Référence</span>
                    <strong>{{ $purchase->reference }}</strong>
                </div>
                <div class="purchase-info__row">
                    <span class="purchase-info__label">Date</span>
                    <span>{{ \App\Helpers\FormatHelper::date($purchase->purchase_date) }}</span>
                </div>
                <div class="purchase-info__row">
                    <span class="purchase-info__label">Fournisseur</span>
                    <span>{{ $purchase->supplier?->name ?? '—' }}</span>
                </div>
                @if($purchase->warehouse)
                <div class="purchase-info__row">
                    <span class="purchase-info__label">Entrepôt</span>
                    <span>{{ $purchase->warehouse->name }}</span>
                </div>
                @endif
                <div class="purchase-summary__divider--light"></div>
                <div class="purchase-info__row">
                    <span class="purchase-info__label">Statut</span>
                    {!! \App\Helpers\FormatHelper::statusBadge($purchase->status) !!}
                </div>
                <div class="purchase-info__row">
                    <span class="purchase-info__label">Paiement</span>
                    {!! \App\Helpers\FormatHelper::statusBadge($purchase->payment_status) !!}
                </div>
                <div class="purchase-summary__divider--light"></div>
                <div class="purchase-info__row">
                    <span class="purchase-info__label">Total</span>
                    <strong class="purchase-info__total">{{ \App\Helpers\FormatHelper::money($purchase->total) }}</strong>
                </div>
                <div class="purchase-info__row">
                    <span class="purchase-info__label">Payé</span>
                    <strong class="purchase-info__paid">{{ \App\Helpers\FormatHelper::money($purchase->amount_paid) }}</strong>
                </div>
                @if($purchase->amount_due > 0)
                <div class="purchase-summary__due-row">
                    <span class="purchase-summary__due-label">Reste dû</span>
                    <strong class="purchase-summary__due-value">{{ \App\Helpers\FormatHelper::money($purchase->amount_due) }}</strong>
                </div>
                @endif
                @if($purchase->returns->sum('total') > 0)
                <div class="purchase-info__row">
                    <span class="purchase-info__label">Retourné</span>
                    <strong class="purchase-summary__returned-value">{{ \App\Helpers\FormatHelper::money($purchase->returns->sum('total')) }}</strong>
                </div>
                @endif
            </div>
        </div>

        @if($purchase->status === 'draft')
        <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn--light btn--full">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
            Modifier le brouillon
        </a>
        <form method="POST" action="{{ route('purchases.confirm', $purchase) }}">
            @csrf @method('PATCH')
            <button type="submit" class="btn btn--confirm-filled btn--full">Confirmer l'achat</button>
        </form>
        @endif

        @if($purchase->payment_status !== 'paid')
        <button @click="open=true" class="btn btn--pay-filled btn--full">
            <span>Enregistrer un paiement</span>
            @if($purchase->status === 'draft')
                <span style="font-size:11px;opacity:.8;">(confirme automatiquement)</span>
            @endif
        </button>
        @endif

        @if($purchase->status === 'confirmed')
        <a href="{{ route('purchase-returns.create', ['purchase_id' => $purchase->id]) }}"
           class="btn btn--return btn--full">
            Créer un retour
        </a>
        @endif

        <form method="POST" action="{{ route('purchases.destroy', $purchase) }}" onsubmit="return confirm('Supprimer cet achat ?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn--danger btn--full">Supprimer</button>
        </form>

        @if($purchase->payments->count() > 0)
        <div class="table-wrapper payments-table">
            <div class="table-wrapper__header"><strong style="font-size:13px;">Paiements effectués</strong></div>
            <table class="data-table">
                <thead><tr><th>Date</th><th>Montant</th><th>Mode</th><th>Compte</th></tr></thead>
                <tbody>
                    @foreach($purchase->payments as $pmt)
                    <tr>
                        <td class="payments-table__date">{{ \App\Helpers\FormatHelper::date($pmt->payment_date) }}</td>
                        <td><strong class="payments-table__amount">{{ \App\Helpers\FormatHelper::money($pmt->amount) }}</strong></td>
                        <td><span class="badge badge--gray">{{ $pmt->payment_method }}</span></td>
                        <td class="payments-table__account">{{ $pmt->paymentAccount?->name ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

    </div>

</div>

{{-- Modale paiement --}}
<div class="modal-overlay" x-show="open" x-cloak @click.self="open=false" x-transition>
    <div class="modal modal--sm">
        <div class="modal__header">
            <h3>Régler cet achat</h3>
            <button class="modal__close" @click="open=false">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal__body">
            <div x-show="success" class="alert alert--success modal-alert" x-text="success"></div>
            <div x-show="error" class="alert alert--danger modal-alert" x-text="error"></div>
            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label>Montant ({{ $currency }}) <span class="required">*</span></label>
                    <input type="number" x-model="amount" min="1" step="1" class="form-control" :class="{'form-control--error': errors.amount}"
                           placeholder="{{ number_format($purchase->amount_due, 0, ',', ' ') }}" required>
                    <span class="form-error" x-show="errors.amount" x-text="errors.amount"></span>
                </div>
                <div class="form-group">
                    <label>Mode de paiement <span class="required">*</span></label>
                    <select x-model="mode" class="form-select" :class="{'form-control--error': errors.mode}">
                        <option value="cash">Espèces</option>
                        <option value="bank_transfer">Virement</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="check">Chèque</option>
                    </select>
                    <span class="form-error" x-show="errors.mode" x-text="errors.mode"></span>
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label>Compte débité <span class="required">*</span></label>
                    <select x-model="accountId" class="form-select" :class="{'form-control--error': errors.accountId}">
                        <option value="">-- Sélectionner --</option>
                        @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->name }} ({{ \App\Helpers\FormatHelper::money($acc->current_balance) }})</option>
                        @endforeach
                    </select>
                    <span class="form-error" x-show="errors.accountId" x-text="errors.accountId"></span>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" @click="open=false" class="btn btn--ghost">Annuler</button>
                <button type="button" @click="submit()" :disabled="loading" class="btn btn--pay-filled">
                    <span x-show="!loading">Valider</span>
                    <span x-show="loading">...</span>
                </button>
            </div>
        </div>
    </div>
</div>

</div>{{-- /x-data --}}
@endsection

@push('scripts')
<script>
function payForm(url) {
    return {
        open: false, loading: false, success: '', error: '',
        amount: '{{ number_format($purchase->amount_due, 0, '.', '') }}', mode: 'cash', accountId: '{{ $accounts->first()->id ?? '' }}',
        errors: { amount: '', mode: '', accountId: '' },
        submit() {
            this.error = ''; this.success = '';
            this.errors = { amount: '', mode: '', accountId: '' };
            if (!this.amount)    this.errors.amount    = 'Le montant est obligatoire.';
            if (!this.mode)      this.errors.mode      = 'Le mode de paiement est obligatoire.';
            if (!this.accountId) this.errors.accountId = 'Le compte de paiement est obligatoire.';
            if (this.errors.amount || this.errors.mode || this.errors.accountId) return;
            this.loading = true;
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    amount: this.amount,
                    payment_mode: this.mode,
                    payment_account_id: this.accountId || null,
                }),
            })
            .then(r => r.json())
            .then(data => {
                this.loading = false;
                if (data.success) {
                    this.success = 'Paiement enregistré.';
                    window.toastAfterReload('Paiement enregistré avec succès.', 'success');
                    setTimeout(() => window.location.reload(), 900);
                } else if (data.errors) {
                    this.errors.amount    = data.errors.amount?.[0]    ?? '';
                    this.errors.mode      = data.errors.payment_mode?.[0] ?? '';
                    this.errors.accountId = data.errors.payment_account_id?.[0] ?? '';
                    this.error = data.message ?? 'Veuillez corriger les erreurs.';
                    window.toast(this.error, 'error');
                } else {
                    this.error = data.message ?? 'Erreur.';
                    window.toast(this.error, 'error');
                }
            })
            .catch(() => { this.loading = false; this.error = 'Erreur réseau.'; window.toast('Erreur réseau.', 'error'); });
        }
    };
}
</script>
@endpush
