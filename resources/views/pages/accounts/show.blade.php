@extends('layouts.app')
@section('title', $paymentAccount->name . ' — Journal')
@section('breadcrumb')
    <a href="{{ route('payment-accounts.index') }}">Comptes</a>
    <span class="sep">/</span>
    <span class="current">{{ $paymentAccount->name }}</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>{{ $paymentAccount->name }}</h2>
        <p>
            {{ match($paymentAccount->type) { 'cash' => 'Caisse', 'bank' => 'Banque', 'mobile_money' => 'Mobile Money', default => $paymentAccount->type } }}
            &nbsp;—&nbsp;Solde actuel :
            <strong style="color:{{ $paymentAccount->current_balance >= 0 ? '#12864B' : '#C4231A' }}">
                {{ \App\Helpers\FormatHelper::money($paymentAccount->current_balance) }}
            </strong>
        </p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('payment-accounts.index') }}" class="btn btn--ghost">Retour</a>
    </div>
</div>

{{-- KPI --}}
<div class="stat-grid" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Solde initial</div>
            <div class="stat-card__value">{{ \App\Helpers\FormatHelper::money($paymentAccount->opening_balance) }}</div>
            <div class="stat-card__trend stat-card__trend--flat">À l'ouverture du compte</div>
        </div>
        <div class="stat-card__icon stat-card__icon--blue">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Encaissements (filtre)</div>
            <div class="stat-card__value" style="color:#12864B;">{{ \App\Helpers\FormatHelper::money($totalCredits) }}</div>
            <div class="stat-card__trend stat-card__trend--up">Entrées sur la période</div>
        </div>
        <div class="stat-card__icon stat-card__icon--green">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Décaissements (filtre)</div>
            <div class="stat-card__value" style="color:#C4231A;">{{ \App\Helpers\FormatHelper::money($totalDebits) }}</div>
            <div class="stat-card__trend stat-card__trend--down">Sorties sur la période</div>
        </div>
        <div class="stat-card__icon stat-card__icon--red">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Solde actuel</div>
            <div class="stat-card__value" style="color:{{ $paymentAccount->current_balance >= 0 ? '#12864B' : '#C4231A' }}">
                {{ \App\Helpers\FormatHelper::money($paymentAccount->current_balance) }}
            </div>
            <div class="stat-card__trend stat-card__trend--flat">Tous mouvements confondus</div>
        </div>
        <div class="stat-card__icon stat-card__icon--{{ $paymentAccount->current_balance >= 0 ? 'green' : 'red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/></svg>
        </div>
    </div>
</div>

{{-- Filtre --}}
<form method="GET" class="table-wrapper" style="padding:16px 20px;margin-bottom:16px;">
    <div class="form-grid form-grid--3" style="gap:12px;align-items:flex-end;">
        <div class="form-group" style="margin-bottom:0">
            <label>Date début</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
        </div>
        <div class="form-group" style="margin-bottom:0">
            <label>Date fin</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
        </div>
        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn--primary">Filtrer</button>
            <a href="{{ route('payment-accounts.show', $paymentAccount) }}" class="btn btn--ghost">Réinitialiser</a>
        </div>
    </div>
</form>

{{-- Journal --}}
<div class="table-wrapper">
    <div class="table-wrapper__header">
        <strong>Journal des mouvements</strong>
        <span style="font-size:13px;color:#64748B;">{{ $journal->count() }} opération(s)</span>
    </div>
    <table class="data-table">
        <thead><tr>
            <th>Date</th>
            <th>Référence</th>
            <th>Libellé</th>
            <th>Mode</th>
            <th style="text-align:right;color:#C4231A;">Débit (sortie)</th>
            <th style="text-align:right;color:#12864B;">Crédit (entrée)</th>
        </tr></thead>
        <tbody>
            @forelse($journal as $row)
            <tr>
                <td>{{ \App\Helpers\FormatHelper::date($row->date) }}</td>
                <td style="font-size:12px;color:#64748B;font-weight:600;">{{ $row->reference }}</td>
                <td>
                    @if($row->link)
                        <a href="{{ $row->link }}" style="color:#1749B3;font-weight:500;">{{ $row->label }}</a>
                    @else
                        {{ $row->label }}
                    @endif
                </td>
                <td>
                    <span class="badge badge--gray">{{ $row->method }}</span>
                </td>
                <td style="text-align:right;">
                    @if($row->debit > 0)
                        <strong style="color:#C4231A;">− {{ \App\Helpers\FormatHelper::money($row->debit) }}</strong>
                    @else
                        <span style="color:#CBD5E1;">—</span>
                    @endif
                </td>
                <td style="text-align:right;">
                    @if($row->credit > 0)
                        <strong style="color:#12864B;">+ {{ \App\Helpers\FormatHelper::money($row->credit) }}</strong>
                    @else
                        <span style="color:#CBD5E1;">—</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center;padding:40px;color:#64748B;">Aucun mouvement sur cette période</td></tr>
            @endforelse
        </tbody>
        @if($journal->count() > 0)
        <tfoot>
            <tr style="border-top:2px solid #E2E8F0;background:#F8FAFC;font-weight:700;">
                <td colspan="4">TOTAL</td>
                <td style="text-align:right;color:#C4231A;">− {{ \App\Helpers\FormatHelper::money($totalDebits) }}</td>
                <td style="text-align:right;color:#12864B;">+ {{ \App\Helpers\FormatHelper::money($totalCredits) }}</td>
            </tr>
            <tr style="background:#F8FAFC;font-weight:700;">
                <td colspan="4">FLUX NET</td>
                @php $net = $totalCredits - $totalDebits; @endphp
                <td colspan="2" style="text-align:right;color:{{ $net >= 0 ? '#12864B' : '#C4231A' }};">
                    {{ $net >= 0 ? '+' : '' }}{{ \App\Helpers\FormatHelper::money($net) }}
                </td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>
@endsection
