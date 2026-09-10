@extends('layouts.app')
@section('title', 'Flux de trésorerie')
@section('breadcrumb')
    <a href="{{ route('payment-accounts.index') }}">Comptes</a>
    <span class="sep">/</span>
    <span class="current">Flux de trésorerie</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>Flux de trésorerie</h2>
        <p>Encaissements et décaissements — {{ $year }}</p>
    </div>
    <div class="page-header__actions">
        <form method="GET" class="account-year-filter">
            <select name="year" class="form-select" onchange="this.form.submit()">
                @foreach($years as $y)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('accounts.balance-sheet') }}" class="btn btn--ghost">Bilan</a>
        <a href="{{ route('accounts.trial-balance') }}" class="btn btn--ghost">Balance</a>
    </div>
</div>

{{-- KPI annuels --}}
<div class="stat-grid mb-24">
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Encaissements {{ $year }}</div>
            <div class="stat-card__value stat-card__value--success">{{ \App\Helpers\FormatHelper::money($totals->encaissements) }}</div>
            <div class="stat-card__trend stat-card__trend--flat">Paiements reçus sur ventes</div>
        </div>
        <div class="stat-card__icon stat-card__icon--green">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Décaissements {{ $year }}</div>
            <div class="stat-card__value stat-card__value--danger">{{ \App\Helpers\FormatHelper::money($totals->decaissements) }}</div>
            <div class="stat-card__trend stat-card__trend--flat">Achats + Dépenses</div>
        </div>
        <div class="stat-card__icon stat-card__icon--red">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Flux net {{ $year }}</div>
            {{-- La couleur dépend d'une valeur dynamique PHP — inline obligatoire --}}
            <div class="stat-card__value" style="color:{{ $totals->net >= 0 ? '#12864B' : '#C4231A' }}">
                {{ \App\Helpers\FormatHelper::money($totals->net) }}
            </div>
            <div class="stat-card__trend stat-card__trend--{{ $totals->net >= 0 ? 'up' : 'down' }}">
                Encaissements – Décaissements
            </div>
        </div>
        <div class="stat-card__icon stat-card__icon--{{ $totals->net >= 0 ? 'green' : 'red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/></svg>
        </div>
    </div>
</div>

{{-- Graphique --}}
<div class="table-wrapper report-chart mb-24">
    <canvas id="cashFlowChart" height="90"></canvas>
</div>

{{-- Tableau mensuel --}}
<div class="table-wrapper">
    <div class="table-wrapper__header">
        <strong>Détail mensuel — {{ $year }}</strong>
    </div>
    <table class="data-table">
        <thead><tr>
            <th>Mois</th>
            <th class="th-success">Encaissements</th>
            <th class="th-info">dont Achats payés</th>
            <th class="th-warning">dont Dépenses</th>
            <th class="th-danger">Total Décaissements</th>
            <th class="text-right">Flux net</th>
        </tr></thead>
        <tbody>
            @foreach($months as $m)
            <tr>
                <td><strong>{{ $m->label }}</strong></td>
                <td class="account-table__amount-in">{{ \App\Helpers\FormatHelper::money($m->encaissements) }}</td>
                <td class="account-table__amount-muted">{{ \App\Helpers\FormatHelper::money($m->dec_achats) }}</td>
                <td class="account-table__amount-muted">{{ \App\Helpers\FormatHelper::money($m->dec_depenses) }}</td>
                <td class="account-table__amount-out">{{ \App\Helpers\FormatHelper::money($m->decaissements) }}</td>
                <td class="text-right">
                    {{-- Couleur dynamique selon positif/négatif --}}
                    <strong style="color:{{ $m->net >= 0 ? '#12864B' : '#C4231A' }}">
                        {{ $m->net >= 0 ? '+' : '' }}{{ \App\Helpers\FormatHelper::money($m->net) }}
                    </strong>
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="account-tfoot">
                <td>TOTAL {{ $year }}</td>
                <td class="account-table__amount-in">{{ \App\Helpers\FormatHelper::money($totals->encaissements) }}</td>
                <td class="account-table__amount-muted">{{ \App\Helpers\FormatHelper::money($totals->dec_achats) }}</td>
                <td class="account-table__amount-muted">{{ \App\Helpers\FormatHelper::money($totals->dec_depenses) }}</td>
                <td class="account-table__amount-out">{{ \App\Helpers\FormatHelper::money($totals->decaissements) }}</td>
                {{-- Couleur dynamique selon positif/négatif --}}
                <td class="text-right font-700" style="color:{{ $totals->net >= 0 ? '#12864B' : '#C4231A' }};">
                    {{ $totals->net >= 0 ? '+' : '' }}{{ \App\Helpers\FormatHelper::money($totals->net) }}
                </td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('cashFlowChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: @json(collect($months)->pluck('label')),
        datasets: [
            {
                label: 'Encaissements',
                data: @json(collect($months)->pluck('encaissements')),
                backgroundColor: 'rgba(34,197,94,0.7)',
                borderRadius: 5,
                borderSkipped: false,
            },
            {
                label: 'Décaissements',
                data: @json(collect($months)->pluck('decaissements')),
                backgroundColor: 'rgba(239,68,68,0.65)',
                borderRadius: 5,
                borderSkipped: false,
            },
            {
                label: 'Flux net',
                data: @json(collect($months)->pluck('net')),
                type: 'line',
                borderColor: '#2563EB',
                backgroundColor: 'rgba(37,99,235,0.1)',
                borderWidth: 2,
                pointRadius: 4,
                tension: 0.3,
                fill: false,
                yAxisID: 'y',
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.dataset.label + ' : ' + new Intl.NumberFormat('fr-FR').format(ctx.parsed.y) + ' ' + window.CURRENCY
                }
            }
        },
        scales: {
            y: {
                ticks: { callback: v => new Intl.NumberFormat('fr-FR', { notation: 'compact' }).format(v) + ' ' + window.CURRENCY }
            }
        }
    }
});
</script>
@endpush
