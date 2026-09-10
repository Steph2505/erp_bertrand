@extends('layouts.app')
@section('title', 'Bilan')
@section('breadcrumb')
    <a href="{{ route('payment-accounts.index') }}">Comptes</a>
    <span class="sep">/</span>
    <span class="current">Bilan</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>Bilan</h2>
        <p>Situation financière au {{ \App\Helpers\FormatHelper::date(now()) }}</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('accounts.trial-balance') }}" class="btn btn--ghost">Balance de vérification</a>
        <a href="{{ route('accounts.cash-flow') }}" class="btn btn--ghost">Flux de trésorerie</a>
    </div>
</div>

{{-- KPI --}}
<div class="stat-grid" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Total Actif</div>
            <div class="stat-card__value">{{ \App\Helpers\FormatHelper::money($totalActif) }}</div>
            <div class="stat-card__trend stat-card__trend--flat">Trésorerie + Stock + Créances</div>
        </div>
        <div class="stat-card__icon stat-card__icon--green">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/></svg>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Total Passif</div>
            <div class="stat-card__value" style="color:#C4231A;">{{ \App\Helpers\FormatHelper::money($totalPassif) }}</div>
            <div class="stat-card__trend stat-card__trend--flat">Dettes fournisseurs</div>
        </div>
        <div class="stat-card__icon stat-card__icon--red">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Situation nette</div>
            <div class="stat-card__value" style="color:{{ $situationNette >= 0 ? '#12864B' : '#C4231A' }}">
                {{ \App\Helpers\FormatHelper::money($situationNette) }}
            </div>
            <div class="stat-card__trend stat-card__trend--{{ $situationNette >= 0 ? 'up' : 'down' }}">
                Actif – Passif
            </div>
        </div>
        <div class="stat-card__icon stat-card__icon--{{ $situationNette >= 0 ? 'green' : 'red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/></svg>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

    {{-- ACTIF --}}
    <div class="table-wrapper">
        <div class="table-wrapper__header">
            <strong style="color:#12864B;">ACTIF</strong>
            <span style="font-size:13px;color:#64748B;">Total : <strong>{{ \App\Helpers\FormatHelper::money($totalActif) }}</strong></span>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>Poste</th>
                <th style="text-align:right">Montant</th>
            </tr></thead>
            <tbody>
                {{-- Trésorerie --}}
                <tr style="background:#F8FAFC;">
                    <td colspan="2" style="font-weight:700;font-size:12px;color:#64748B;text-transform:uppercase;padding:8px 16px;">
                        Trésorerie — {{ \App\Helpers\FormatHelper::money($totalTresorerie) }}
                    </td>
                </tr>
                @foreach($accounts as $acc)
                <tr>
                    <td style="padding-left:28px;">
                        {{ $acc->name }}
                        <span class="badge badge--{{ $acc->type === 'cash' ? 'green' : ($acc->type === 'bank' ? 'blue' : 'yellow') }}" style="margin-left:6px;">
                            {{ match($acc->type) { 'cash' => 'Caisse', 'bank' => 'Banque', 'mobile_money' => 'Mobile Money', default => $acc->type } }}
                        </span>
                    </td>
                    <td style="text-align:right;font-weight:600;">{{ \App\Helpers\FormatHelper::money($acc->current_balance) }}</td>
                </tr>
                @endforeach

                {{-- Stock --}}
                <tr style="background:#F8FAFC;">
                    <td colspan="2" style="font-weight:700;font-size:12px;color:#64748B;text-transform:uppercase;padding:8px 16px;">
                        Valeur du stock (coût d'achat)
                    </td>
                </tr>
                <tr>
                    <td style="padding-left:28px;">Stocks de marchandises</td>
                    <td style="text-align:right;font-weight:600;">{{ \App\Helpers\FormatHelper::money($stockValue) }}</td>
                </tr>

                {{-- Créances --}}
                <tr style="background:#F8FAFC;">
                    <td colspan="2" style="font-weight:700;font-size:12px;color:#64748B;text-transform:uppercase;padding:8px 16px;">
                        Créances clients
                    </td>
                </tr>
                <tr>
                    <td style="padding-left:28px;">Ventes non intégralement encaissées</td>
                    <td style="text-align:right;font-weight:600;">{{ \App\Helpers\FormatHelper::money($creancesClients) }}</td>
                </tr>

                {{-- Total --}}
                <tr style="border-top:2px solid #E2E8F0;">
                    <td style="font-weight:700;">TOTAL ACTIF</td>
                    <td style="text-align:right;font-weight:700;color:#12864B;font-size:15px;">{{ \App\Helpers\FormatHelper::money($totalActif) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- PASSIF --}}
    <div class="table-wrapper">
        <div class="table-wrapper__header">
            <strong style="color:#C4231A;">PASSIF</strong>
            <span style="font-size:13px;color:#64748B;">Total : <strong>{{ \App\Helpers\FormatHelper::money($totalPassif) }}</strong></span>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>Poste</th>
                <th style="text-align:right">Montant</th>
            </tr></thead>
            <tbody>
                {{-- Dettes fournisseurs --}}
                <tr style="background:#F8FAFC;">
                    <td colspan="2" style="font-weight:700;font-size:12px;color:#64748B;text-transform:uppercase;padding:8px 16px;">
                        Dettes fournisseurs
                    </td>
                </tr>
                <tr>
                    <td style="padding-left:28px;">Achats non intégralement réglés</td>
                    <td style="text-align:right;font-weight:600;">{{ \App\Helpers\FormatHelper::money($dettesFournisseurs) }}</td>
                </tr>

                {{-- Situation nette --}}
                <tr style="background:#F8FAFC;">
                    <td colspan="2" style="font-weight:700;font-size:12px;color:#64748B;text-transform:uppercase;padding:8px 16px;">
                        Situation nette (Capitaux propres)
                    </td>
                </tr>
                <tr>
                    <td style="padding-left:28px;">Actif – Passif</td>
                    <td style="text-align:right;font-weight:600;color:{{ $situationNette >= 0 ? '#12864B' : '#C4231A' }};">
                        {{ \App\Helpers\FormatHelper::money($situationNette) }}
                    </td>
                </tr>

                {{-- Total --}}
                <tr style="border-top:2px solid #E2E8F0;">
                    <td style="font-weight:700;">TOTAL PASSIF + SITUATION NETTE</td>
                    <td style="text-align:right;font-weight:700;color:#C4231A;font-size:15px;">{{ \App\Helpers\FormatHelper::money($totalActif) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Note d'équilibre --}}
        <div style="padding:16px 20px;border-top:1px solid #E2E8F0;font-size:12px;color:#64748B;">
            @if(abs($totalActif - ($totalPassif + $situationNette)) < 1)
                <span style="color:#12864B;font-weight:600;">✓ Bilan équilibré</span> — Actif = Passif + Situation nette
            @else
                <span style="color:#C4231A;">⚠ Écart de {{ \App\Helpers\FormatHelper::money(abs($totalActif - $totalPassif - $situationNette)) }}</span>
            @endif
        </div>
    </div>

</div>
@endsection
