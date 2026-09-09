@extends('layouts.app')
@section('title', 'Vente ' . $sale->reference)
@section('breadcrumb')
    <a href="{{ route('sales.index') }}">Ventes</a>
    <span class="sep">/</span>
    <span class="current">{{ $sale->reference }}</span>
@endsection

@section('content')
<div x-data="payForm('{{ route('sales.pay', $sale) }}')" @keydown.escape.window="open=false">
<div class="page-header">
    <div class="page-header__title">
        <h2>{{ $sale->reference }}</h2>
        <p>Vente du {{ \App\Helpers\FormatHelper::date($sale->sale_date) }}</p>
    </div>
    <div class="page-header__actions">
        @if($sale->status === 'draft')
        <form method="POST" action="{{ route('sales.confirm', $sale) }}">
            @csrf @method('PATCH')
            <button type="submit" class="btn btn--primary">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="sale-show__confirm-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                Confirmer la vente
            </button>
        </form>
        @endif
        <a href="{{ route('print.sale', $sale) }}" target="_blank" class="btn btn--light">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659"/></svg>
            Imprimer
        </a>
        <a href="{{ route('sales.index') }}" class="btn btn--ghost">Retour</a>
    </div>
</div>

<div class="sale-show__layout">

    <div class="sale-show__main">
        <div class="table-wrapper">
            <div class="table-wrapper__header">
                <strong>Articles vendus</strong>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Article</th>
                        <th class="th-center">Qté</th>
                        <th>Prix unit.</th>
                        <th class="th-right">Sous-total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $item)
                        <tr>
                            <td>
                                <div class="sale-show__item-name">{{ $item->item_name }}</div>
                                @if($item->item_type === 'pack')
                                    <span class="badge badge--blue" style="font-size:10px;">Pack</span>
                                @endif
                            </td>
                            <td style="text-align:center">{{ $item->quantity }}</td>
                            <td>{{ \App\Helpers\FormatHelper::money($item->unit_price) }}</td>
                            <td class="sale-show__tfoot-label" style="text-align:right">{{ \App\Helpers\FormatHelper::money($item->subtotal) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="sale-show__tfoot-label">Total</td>
                        <td class="sale-show__tfoot-total">
                            {{ \App\Helpers\FormatHelper::money($sale->total) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if($sale->note)
        <div class="card sale-show__note-card">
            <h4 class="sale-show__note-label">NOTE</h4>
            <p class="sale-show__note-text">{{ $sale->note }}</p>
        </div>
        @endif
    </div>

    <div class="sale-show__sidebar">
        <div class="card sale-show__info-card">
            <h3 class="sale-show__info-title">Informations</h3>
            <div class="sale-show__info-rows">
                <div class="sale-show__info-row">
                    <span class="sale-show__info-label">Référence</span>
                    <strong>{{ $sale->reference }}</strong>
                </div>
                <div class="sale-show__info-row">
                    <span class="sale-show__info-label">Date</span>
                    <span>{{ \App\Helpers\FormatHelper::date($sale->sale_date) }}</span>
                </div>
                <div class="sale-show__info-row">
                    <span class="sale-show__info-label">Client</span>
                    <span>{{ $sale->customer?->name ?? 'Client comptoir' }}</span>
                </div>
                @if($sale->warehouse)
                <div class="sale-show__info-row">
                    <span class="sale-show__info-label">Entrepôt</span>
                    <span>{{ $sale->warehouse->name }}</span>
                </div>
                @endif
                <div class="sale-show__info-divider"></div>
                <div class="sale-show__info-row">
                    <span class="sale-show__info-label">Statut facture</span>
                    {!! \App\Helpers\FormatHelper::statusBadge($sale->status) !!}
                </div>
                <div class="sale-show__info-row">
                    <span class="sale-show__info-label">Statut paiement</span>
                    {!! \App\Helpers\FormatHelper::statusBadge($sale->payment_status) !!}
                </div>
                <div class="sale-show__info-row">
                    <span class="sale-show__info-label">Total</span>
                    <strong class="sale-show__total-value">{{ \App\Helpers\FormatHelper::money($sale->total) }}</strong>
                </div>
                <div class="sale-show__info-row">
                    <span class="sale-show__info-label">Payé</span>
                    <strong>{{ \App\Helpers\FormatHelper::money($sale->amount_paid) }}</strong>
                </div>
                @if($sale->amount_due > 0)
                <div class="sale-show__info-row">
                    <span class="sale-show__info-label sale-show__info-label--danger">Reste à payer</span>
                    <strong class="sale-show__due-value">{{ \App\Helpers\FormatHelper::money($sale->amount_due) }}</strong>
                </div>
                @endif
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert--success" style="margin-bottom:4px;">{{ session('success') }}</div>
        @endif
        @if($errors->has('sale'))
        <div class="alert alert--danger" style="margin-bottom:4px;">{{ $errors->first('sale') }}</div>
        @endif

        @if($sale->payment_status !== 'paid' && $sale->status === 'confirmed')
        <button @click="open=true" class="btn btn--primary sale-show__btn-full">
            Enregistrer un paiement
        </button>
        @endif

        @if($sale->status !== 'confirmed')
        <form method="POST" action="{{ route('sales.destroy', $sale) }}" onsubmit="return confirm('Supprimer définitivement cette vente ?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn--danger sale-show__btn-full">
                Supprimer cette vente
            </button>
        </form>
        @else
        <div class="sale-show__locked-notice">
            Vente confirmée — suppression impossible
        </div>
        @endif

        @if($sale->payments->count() > 0)
        <div class="table-wrapper" style="margin-top:4px;">
            <div class="table-wrapper__header"><strong style="font-size:13px;">Paiements reçus</strong></div>
            <table class="data-table">
                <thead><tr><th>Date</th><th>Montant</th><th>Mode</th><th>Compte</th></tr></thead>
                <tbody>
                    @foreach($sale->payments as $pmt)
                    <tr>
                        <td class="sale-show__pmt-date">{{ \App\Helpers\FormatHelper::date($pmt->payment_date) }}</td>
                        <td><strong class="sale-show__pmt-amount">{{ \App\Helpers\FormatHelper::money($pmt->amount) }}</strong></td>
                        <td><span class="badge badge--gray">{{ $pmt->payment_method }}</span></td>
                        <td class="sale-show__pmt-meta">{{ $pmt->paymentAccount?->name ?? '—' }}</td>
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
            <h3>Enregistrer un paiement</h3>
            <button class="modal__close" @click="open=false">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal__body">
            <div x-show="success" class="alert alert--success" style="margin-bottom:12px;" x-text="success"></div>
            <div x-show="error" class="alert alert--danger" style="margin-bottom:12px;" x-text="error"></div>
            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label>Montant ({{ $currency }}) <span class="required">*</span></label>
                    <input type="number" x-model="amount" min="1" step="1" class="form-control"
                           placeholder="{{ number_format($sale->amount_due, 0, ',', ' ') }}" required>
                </div>
                <div class="form-group">
                    <label>Mode de paiement <span class="required">*</span></label>
                    <select x-model="mode" class="form-select">
                        <option value="cash">Espèces</option>
                        <option value="bank_transfer">Virement</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="check">Chèque</option>
                    </select>
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label>Compte de paiement</label>
                    <select x-model="accountId" class="form-select">
                        <option value="">— Compte par défaut —</option>
                        @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->name }} ({{ \App\Helpers\FormatHelper::money($acc->current_balance) }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="sale-show__modal-footer">
                <button type="button" @click="open=false" class="btn btn--ghost">Annuler</button>
                <button type="button" @click="submit()" :disabled="loading" class="btn btn--primary">
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
        amount: '', mode: 'cash', accountId: '',
        submit() {
            this.error = ''; this.success = ''; this.loading = true;
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
                    setTimeout(() => window.location.reload(), 900);
                } else {
                    this.error = data.message ?? 'Erreur.';
                }
            })
            .catch(() => { this.loading = false; this.error = 'Erreur réseau.'; });
        }
    };
}
</script>
@endpush
