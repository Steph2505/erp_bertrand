@extends('layouts.app')
@section('title', 'Balance de vérification')
@section('breadcrumb')
    <a href="{{ route('payment-accounts.index') }}">Comptes</a>
    <span class="sep">/</span>
    <span class="current">Balance de vérification</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>Balance de vérification</h2>
        <p>Mouvements par compte de paiement — {{ $year }}</p>
    </div>
    <div class="page-header__actions">
        <form method="GET" style="display:flex;gap:8px;align-items:center;">
            <select name="year" class="form-select" style="width:auto;" onchange="this.form.submit()">
                @foreach($years as $y)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('accounts.balance-sheet') }}" class="btn btn--ghost">Bilan</a>
        <a href="{{ route('accounts.cash-flow') }}" class="btn btn--ghost">Flux de trésorerie</a>
    </div>
</div>

<div class="table-wrapper">
    <div class="table-wrapper__header">
        <strong>Balance par compte — exercice {{ $year }}</strong>
    </div>
    <table class="data-table">
        <thead><tr>
            <th>Compte</th>
            <th>Type</th>
            <th style="text-align:right">Solde initial</th>
            <th style="text-align:right;color:#12864B;">Encaissements</th>
            <th style="text-align:right;color:#C4231A;">Décaissements</th>
            <th style="text-align:right;color:#2563EB;">dont Achats</th>
            <th style="text-align:right;color:#B45309;">dont Dépenses</th>
            <th style="text-align:right">Solde calculé</th>
            <th style="text-align:right">Solde réel</th>
            <th style="text-align:right">Écart</th>
        </tr></thead>
        <tbody>
            @php
                $totOpening = 0; $totEnc = 0; $totDec = 0;
                $totDecAchats = 0; $totDecDep = 0;
                $totCalc = 0; $totReel = 0;
            @endphp
            @forelse($accounts as $acc)
                @php
                    $ecart = round($acc->solde_calcule - $acc->solde_reel, 2);
                    $totOpening    += $acc->opening;
                    $totEnc        += $acc->encaissements;
                    $totDec        += $acc->decaissements;
                    $totDecAchats  += $acc->dec_achats ?? 0;
                    $totDecDep     += $acc->dec_depenses ?? 0;
                    $totCalc       += $acc->solde_calcule;
                    $totReel       += $acc->solde_reel;
                @endphp
                <tr>
                    <td><strong>{{ $acc->name }}</strong></td>
                    <td>
                        <span class="badge badge--{{ $acc->type === 'cash' ? 'green' : ($acc->type === 'bank' ? 'blue' : 'yellow') }}">
                            {{ match($acc->type) { 'cash' => 'Caisse', 'bank' => 'Banque', 'mobile_money' => 'Mobile Money', default => $acc->type } }}
                        </span>
                    </td>
                    <td style="text-align:right">{{ \App\Helpers\FormatHelper::money($acc->opening) }}</td>
                    <td style="text-align:right;color:#12864B;font-weight:600;">+ {{ \App\Helpers\FormatHelper::money($acc->encaissements) }}</td>
                    <td style="text-align:right;color:#C4231A;font-weight:600;">− {{ \App\Helpers\FormatHelper::money($acc->decaissements) }}</td>
                    <td style="text-align:right;color:#64748B;font-size:12px;">{{ \App\Helpers\FormatHelper::money($acc->dec_achats ?? 0) }}</td>
                    <td style="text-align:right;color:#64748B;font-size:12px;">{{ \App\Helpers\FormatHelper::money($acc->dec_depenses ?? 0) }}</td>
                    <td style="text-align:right;font-weight:600;">{{ \App\Helpers\FormatHelper::money($acc->solde_calcule) }}</td>
                    <td style="text-align:right;font-weight:600;">{{ \App\Helpers\FormatHelper::money($acc->solde_reel) }}</td>
                    <td style="text-align:right;">
                        @if(abs($ecart) < 1)
                            <span class="badge badge--green">✓</span>
                        @else
                            <span class="badge badge--red">{{ \App\Helpers\FormatHelper::money(abs($ecart)) }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" style="text-align:center;padding:40px;color:#64748B;">Aucun compte configuré</td></tr>
            @endforelse
        </tbody>
        @if($accounts->count() > 1)
        <tfoot>
            <tr style="border-top:2px solid #E2E8F0;background:#F8FAFC;font-weight:700;">
                <td colspan="2">TOTAL</td>
                <td style="text-align:right">{{ \App\Helpers\FormatHelper::money($totOpening) }}</td>
                <td style="text-align:right;color:#12864B;">+ {{ \App\Helpers\FormatHelper::money($totEnc) }}</td>
                <td style="text-align:right;color:#C4231A;">− {{ \App\Helpers\FormatHelper::money($totDec) }}</td>
                <td style="text-align:right;color:#64748B;font-size:12px;">{{ \App\Helpers\FormatHelper::money($totDecAchats) }}</td>
                <td style="text-align:right;color:#64748B;font-size:12px;">{{ \App\Helpers\FormatHelper::money($totDecDep) }}</td>
                <td style="text-align:right">{{ \App\Helpers\FormatHelper::money($totCalc) }}</td>
                <td style="text-align:right">{{ \App\Helpers\FormatHelper::money($totReel) }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

<div style="margin-top:16px;padding:16px 20px;background:#F8FAFC;border-radius:8px;border:1px solid #E2E8F0;font-size:13px;color:#64748B;">
    <strong>Lecture :</strong>
    Solde calculé = Solde initial + Encaissements − Décaissements.
    L'écart entre le solde calculé et le solde réel indique des mouvements enregistrés hors paiement tracé (ex : solde initial manuel, ajustements).
</div>
@endsection
