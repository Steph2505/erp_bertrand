@extends('layouts.app')
@section('title', 'Devis ' . $quotation->reference)
@section('breadcrumb')
<a href="{{ route('quotations.index') }}">Devis</a>
<span class="current">{{ $quotation->reference }}</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>{{ $quotation->reference }}</h2>
        <p><span class="badge {{ \App\Models\Quotation::statusBadgeClass($quotation->status) }}">{{ \App\Models\Quotation::statusLabel($quotation->status) }}</span></p>
    </div>
    <div class="page-header__actions quotation-header-actions">
        {{-- Changer statut --}}
        <form method="POST" action="{{ route('quotations.status', $quotation) }}" class="quotation-header-actions__status-form">
            @csrf @method('PATCH')
            <select name="status" class="form-select quotation-header-actions__status-select">
                <option value="draft"    {{ $quotation->status === 'draft'    ? 'selected' : '' }}>Brouillon</option>
                <option value="sent"     {{ $quotation->status === 'sent'     ? 'selected' : '' }}>Envoyé</option>
                <option value="accepted" {{ $quotation->status === 'accepted' ? 'selected' : '' }}>Accepté</option>
                <option value="rejected" {{ $quotation->status === 'rejected' ? 'selected' : '' }}>Refusé</option>
                <option value="expired"  {{ $quotation->status === 'expired'  ? 'selected' : '' }}>Expiré</option>
            </select>
            <button type="submit" class="btn btn--ghost btn--sm">Mettre à jour</button>
        </form>

        @if($quotation->status === 'accepted')
        <form method="POST" action="{{ route('quotations.convert', $quotation) }}" @submit.prevent="window.confirmDialog('Convertir ce devis en vente ?', {confirmLabel:'Convertir'}).then(ok => ok && $el.submit())">
            @csrf
            <button class="btn btn--primary">→ Convertir en vente</button>
        </form>
        @endif

        <form method="POST" action="{{ route('quotations.destroy', $quotation) }}" @submit.prevent="window.confirmDialog('Supprimer ce devis ?', {variant:'danger', confirmLabel:'Supprimer'}).then(ok => ok && $el.submit())">
            @csrf @method('DELETE')
            <button class="btn btn--danger btn--sm btn--icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
            </button>
        </form>
    </div>
</div>

<div class="quotation-layout">
    <div class="quotation-layout__main">
        <div class="card p-24">
            <div class="quotation-parties">
                <div>
                    <div class="quotation-parties__label">Client</div>
                    <div class="quotation-parties__name">{{ $quotation->customer?->name ?? 'Client comptoir' }}</div>
                    @if($quotation->customer?->phone)
                        <div class="quotation-parties__meta">{{ $quotation->customer->phone }}</div>
                    @endif
                </div>
                <div>
                    <div class="quotation-parties__label">Dates</div>
                    <div class="text-md">Date : <strong>{{ \App\Helpers\FormatHelper::date($quotation->quotation_date) }}</strong></div>
                    @if($quotation->expiry_date)
                    <div class="{{ $quotation->expiry_date->isPast() ? 'quotation-parties__expiry-past' : 'quotation-parties__expiry-ok' }}">
                        Expiration : <strong>{{ \App\Helpers\FormatHelper::date($quotation->expiry_date) }}</strong>
                    </div>
                    @endif
                </div>
            </div>

            <table class="data-table">
                <thead><tr>
                    <th>#</th>
                    <th>Désignation</th>
                    <th class="text-center">Qté</th>
                    <th class="text-right">Prix unit.</th>
                    <th class="text-right">Total</th>
                </tr></thead>
                <tbody>
                    @foreach($quotation->items as $i => $item)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td>{{ $item->item_name }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">{{ \App\Helpers\FormatHelper::money($item->unit_price) }}</td>
                        <td class="text-right font-600">{{ \App\Helpers\FormatHelper::money($item->subtotal) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($quotation->note)
        <div class="card quotation-note">
            <strong class="quotation-note__label">Note :</strong>
            <p class="quotation-note__text">{{ $quotation->note }}</p>
        </div>
        @endif
    </div>

    <div class="card quotation-summary">
        <h3 class="quotation-summary__title">Récapitulatif</h3>
        <div class="quotation-summary__row">
            <span>Sous-total</span>
            <span>{{ \App\Helpers\FormatHelper::money($quotation->subtotal) }}</span>
        </div>
        @if($quotation->discount > 0)
        <div class="quotation-summary__row quotation-summary__row--discount">
            <span>Remise</span>
            <span>-{{ \App\Helpers\FormatHelper::money($quotation->discount) }}</span>
        </div>
        @endif
        <div class="quotation-summary__row--total">
            <span>TOTAL</span>
            <span class="quotation-summary__total-value">{{ \App\Helpers\FormatHelper::money($quotation->total) }}</span>
        </div>
        <p class="quotation-summary__note-hint">
            Créé par {{ $quotation->createdBy?->name ?? '—' }}<br>
            le {{ \App\Helpers\FormatHelper::date($quotation->created_at) }}
        </p>
    </div>
</div>
@endsection
