@extends('layouts.app')
@section('title', $paymentAccount->name . ' — Journal')
@section('breadcrumb')
    <a href="{{ route('payment-accounts.index') }}">Comptes</a>
    <span class="sep">/</span>
    <span class="current">{{ $paymentAccount->name }}</span>
@endsection

@section('content')
<div x-data="{...listPage('{{ route('payment-accounts.api.show', $paymentAccount) }}')}" x-init="filters = {date_from: '', date_to: ''}; fetch()">
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
            <div class="stat-card__value" style="color:#12864B;" x-text="extra.total_credits_display ?? '—'">—</div>
            <div class="stat-card__trend stat-card__trend--up">Entrées sur la période</div>
        </div>
        <div class="stat-card__icon stat-card__icon--green">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Décaissements (filtre)</div>
            <div class="stat-card__value" style="color:#C4231A;" x-text="extra.total_debits_display ?? '—'">—</div>
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
<div class="table-wrapper" style="padding:16px 20px;margin-bottom:16px;">
    <div class="form-grid form-grid--3" style="gap:12px;align-items:flex-end;">
        <div class="form-group" style="margin-bottom:0">
            <label>Date début</label>
            <input type="date" x-model="filters.date_from" @change="reset()" class="form-control">
        </div>
        <div class="form-group" style="margin-bottom:0">
            <label>Date fin</label>
            <input type="date" x-model="filters.date_to" @change="reset()" class="form-control">
        </div>
        <div style="display:flex;gap:8px;">
            <button type="button" @click="clearFilters()" class="btn btn--ghost">Réinitialiser</button>
        </div>
    </div>
</div>

{{-- Journal --}}
<div class="table-wrapper">
    <div class="table-wrapper__header">
        <strong>Journal des mouvements</strong>
        <span style="font-size:13px;color:#64748B;" x-text="total + ' opération(s)'">—</span>
    </div>
    <div style="position:relative;">
        <div x-show="loading && rows.length > 0" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
            <svg style="width:28px;height:28px;color:#1749B3;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
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
                <template x-if="loading && rows.length === 0">
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:#64748B;">Chargement...</td></tr>
                </template>
                <template x-if="!loading && rows.length === 0">
                    <tr><td colspan="6" style="text-align:center;padding:40px;color:#64748B;">Aucun mouvement sur cette période</td></tr>
                </template>
                <template x-for="(row, idx) in rows" :key="idx">
                    <tr>
                        <td x-text="row.date"></td>
                        <td style="font-size:12px;color:#64748B;font-weight:600;" x-text="row.reference"></td>
                        <td>
                            <template x-if="row.link">
                                <a :href="row.link" style="color:#1749B3;font-weight:500;" x-text="row.label"></a>
                            </template>
                            <template x-if="!row.link">
                                <span x-text="row.label"></span>
                            </template>
                        </td>
                        <td>
                            <span class="badge badge--gray" x-text="row.method"></span>
                        </td>
                        <td style="text-align:right;">
                            <template x-if="row.debit_display">
                                <strong style="color:#C4231A;" x-text="'− ' + row.debit_display"></strong>
                            </template>
                            <template x-if="!row.debit_display">
                                <span style="color:#CBD5E1;">—</span>
                            </template>
                        </td>
                        <td style="text-align:right;">
                            <template x-if="row.credit_display">
                                <strong style="color:#12864B;" x-text="'+ ' + row.credit_display"></strong>
                            </template>
                            <template x-if="!row.credit_display">
                                <span style="color:#CBD5E1;">—</span>
                            </template>
                        </td>
                    </tr>
                </template>
            </tbody>
            <tfoot x-show="rows.length > 0">
                <tr style="border-top:2px solid #E2E8F0;background:#F8FAFC;font-weight:700;">
                    <td colspan="4">TOTAL</td>
                    <td style="text-align:right;color:#C4231A;" x-text="'− ' + (extra.total_debits_display ?? '—')"></td>
                    <td style="text-align:right;color:#12864B;" x-text="'+ ' + (extra.total_credits_display ?? '—')"></td>
                </tr>
                <tr style="background:#F8FAFC;font-weight:700;">
                    <td colspan="4">FLUX NET</td>
                    <td colspan="2" :style="'text-align:right;color:' + (extra.net_positive ? '#12864B' : '#C4231A')" x-text="extra.net_display ?? '—'"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="table-wrapper__footer">
        <span x-text="from + '–' + to + ' sur ' + total"></span>
        <div style="display:flex;gap:4px;" x-show="lastPage > 1">
            <button @click="goTo(currentPage-1)" :disabled="currentPage<=1||loading" class="btn btn--ghost btn--sm btn--icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg></button>
            <template x-for="p in pages" :key="p"><button @click="p!=='…'&&goTo(p)" class="btn btn--sm" :class="p===currentPage?'btn--primary':'btn--ghost'" :disabled="p==='…'||loading" x-text="p" style="min-width:34px;justify-content:center;"></button></template>
            <button @click="goTo(currentPage+1)" :disabled="currentPage>=lastPage||loading" class="btn btn--ghost btn--sm btn--icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg></button>
        </div>
    </div>
</div>
</div>
@include('components.list-page-script')
@endsection
