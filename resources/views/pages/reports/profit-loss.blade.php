@extends('layouts.app')
@section('title', 'Profit / Perte')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Profit / Perte</span>@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>Profit / Perte</h2>
        <p>Du {{ \App\Helpers\FormatHelper::date($from) }} au {{ \App\Helpers\FormatHelper::date($to) }}</p>
    </div>
</div>

<form method="GET" class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0"><label>Du</label><input type="date" name="date_from" value="{{ $from }}" class="form-control"></div>
        <div class="form-group" style="margin:0"><label>Au</label><input type="date" name="date_to" value="{{ $to }}" class="form-control"></div>
        <button class="btn btn--primary">Filtrer</button>
        <a href="{{ route('reports.profit-loss') }}" class="btn btn--ghost">Réinitialiser</a>
    </div>
</form>

{{-- KPI --}}
<div class="stat-grid" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Chiffre d'affaires</div>
            <div class="stat-card__value" style="color:#22C55E;">{{ \App\Helpers\FormatHelper::money($revenue) }}</div>
            <div class="stat-card__trend stat-card__trend--flat">Ventes confirmées</div>
        </div>
        <div class="stat-card__icon stat-card__icon--green">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Coût des achats</div>
            <div class="stat-card__value" style="color:#3B82F6;">{{ \App\Helpers\FormatHelper::money($cogs) }}</div>
            <div class="stat-card__trend stat-card__trend--flat">Achats confirmés</div>
        </div>
        <div class="stat-card__icon stat-card__icon--blue">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Marge brute</div>
            <div class="stat-card__value" style="color:{{ $grossProfit >= 0 ? '#22C55E' : '#EF4444' }}">{{ \App\Helpers\FormatHelper::money($grossProfit) }}</div>
            <div class="stat-card__trend stat-card__trend--flat">CA – Achats</div>
        </div>
        <div class="stat-card__icon stat-card__icon--{{ $grossProfit >= 0 ? 'green' : 'red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/></svg>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Résultat net</div>
            <div class="stat-card__value" style="color:{{ $netProfit >= 0 ? '#22C55E' : '#EF4444' }}">{{ \App\Helpers\FormatHelper::money($netProfit) }}</div>
            <div class="stat-card__trend stat-card__trend--{{ $netProfit >= 0 ? 'up' : 'down' }}">Marge – Dépenses ({{ \App\Helpers\FormatHelper::money($expenses) }})</div>
        </div>
        <div class="stat-card__icon stat-card__icon--{{ $netProfit >= 0 ? 'green' : 'red' }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $netProfit >= 0 ? 'M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941' : 'M2.25 6 9 12.75l4.286-4.286a11.948 11.948 0 0 1 4.306 6.43l.776 2.898m0 0 3.182-5.511m-3.182 5.51-5.511-3.181' }}"/></svg>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">

    {{-- Graphique évolution --}}
    <div class="table-wrapper" style="padding:20px;">
        <strong style="display:block;margin-bottom:16px;">Évolution sur 12 mois</strong>
        <canvas id="plChart" height="100"></canvas>
    </div>

    {{-- Dépenses par catégorie --}}
    <div class="table-wrapper">
        <div class="table-wrapper__header"><strong>Dépenses par catégorie</strong></div>
        <table class="data-table">
            <thead><tr><th>Catégorie</th><th style="text-align:right">Montant</th></tr></thead>
            <tbody>
                @forelse($expensesByCategory as $cat)
                <tr>
                    <td>{{ $cat->name }}</td>
                    <td style="text-align:right;font-weight:600;color:#EF4444;">{{ \App\Helpers\FormatHelper::money($cat->expenses_sum_amount) }}</td>
                </tr>
                @empty
                <tr><td colspan="2" style="text-align:center;padding:24px;color:#64748B;">Aucune dépense</td></tr>
                @endforelse
                @if($expenses > 0)
                <tr style="border-top:2px solid #E2E8F0;font-weight:700;">
                    <td>TOTAL</td>
                    <td style="text-align:right;color:#EF4444;">{{ \App\Helpers\FormatHelper::money($expenses) }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

{{-- Tableau mensuel récapitulatif --}}
<div class="table-wrapper" style="margin-top:20px;">
    <div class="table-wrapper__header"><strong>Récapitulatif mensuel (12 derniers mois)</strong></div>
    <table class="data-table">
        <thead><tr>
            <th>Mois</th>
            <th style="text-align:right;color:#22C55E;">CA</th>
            <th style="text-align:right;color:#3B82F6;">Achats</th>
            <th style="text-align:right;color:#F59E0B;">Dépenses</th>
            <th style="text-align:right">Résultat net</th>
        </tr></thead>
        <tbody>
            @foreach($monthly as $m)
            @php $net = $m['revenue'] - $m['cogs'] - $m['expenses']; @endphp
            <tr>
                <td><strong>{{ $m['label'] }}</strong></td>
                <td style="text-align:right">{{ \App\Helpers\FormatHelper::money($m['revenue']) }}</td>
                <td style="text-align:right">{{ \App\Helpers\FormatHelper::money($m['cogs']) }}</td>
                <td style="text-align:right">{{ \App\Helpers\FormatHelper::money($m['expenses']) }}</td>
                <td style="text-align:right;font-weight:600;color:{{ $net >= 0 ? '#22C55E' : '#EF4444' }}">
                    {{ $net >= 0 ? '+' : '' }}{{ \App\Helpers\FormatHelper::money($net) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('plChart'), {
    type: 'bar',
    data: {
        labels: @json(collect($monthly)->pluck('label')),
        datasets: [
            { label: 'CA', data: @json(collect($monthly)->pluck('revenue')), backgroundColor: 'rgba(34,197,94,.7)', borderRadius: 4 },
            { label: 'Achats', data: @json(collect($monthly)->pluck('cogs')), backgroundColor: 'rgba(59,130,246,.65)', borderRadius: 4 },
            { label: 'Dépenses', data: @json(collect($monthly)->pluck('expenses')), backgroundColor: 'rgba(245,158,11,.65)', borderRadius: 4 },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' }, tooltip: { callbacks: { label: c => c.dataset.label+' : '+new Intl.NumberFormat('fr-FR').format(c.parsed.y)+' ' + window.CURRENCY } } },
        scales: { y: { ticks: { callback: v => new Intl.NumberFormat('fr-FR',{notation:'compact'}).format(v)+' ' + window.CURRENCY } } }
    }
});
</script>
@endpush
