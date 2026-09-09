@extends('layouts.app')

@section('title', 'Rapport de dépenses')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Dépenses</span>@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title"><h2>Rapport de dépenses</h2><p>Charges d'exploitation + achats fournisseurs payés</p></div>
    <div class="page-header__actions">
        <a href="{{ route('expenses.index') }}" class="btn btn--ghost">Journal des dépenses</a>
    </div>
</div>

<form method="GET" class="table-wrapper report-filter">
    <div class="report-filter__row">
        <div class="form-group mb-0"><label>Du</label><input type="date" name="date_from" value="{{ $from }}" class="form-control"></div>
        <div class="form-group mb-0"><label>Au</label><input type="date" name="date_to" value="{{ $to }}" class="form-control"></div>
        <button class="btn btn--primary">Filtrer</button>
        <a href="{{ route('reports.expenses') }}" class="btn btn--ghost">Réinitialiser</a>
    </div>
</form>

{{-- KPI --}}
<div class="stat-grid mb-24">
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Charges d'exploitation</div>
            <div class="stat-card__value stat-card__value--danger">{{ \App\Helpers\FormatHelper::money($totalExpenses) }}</div>
            <div class="stat-card__trend stat-card__trend--flat">Loyer, salaires, etc.</div>
        </div>
        <div class="stat-card__icon stat-card__icon--red">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Achats fournisseurs payés</div>
            <div class="stat-card__value stat-card__value--info">{{ \App\Helpers\FormatHelper::money($totalPurchases) }}</div>
            <div class="stat-card__trend stat-card__trend--flat">Coût des marchandises</div>
        </div>
        <div class="stat-card__icon stat-card__icon--blue">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Total charges</div>
            <div class="stat-card__value stat-card__value--purple">{{ \App\Helpers\FormatHelper::money($total) }}</div>
            <div class="stat-card__trend stat-card__trend--down">Toutes charges confondues</div>
        </div>
        <div class="stat-card__icon stat-card__icon--purple">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Catégories actives</div>
            <div class="stat-card__value">{{ $byCategory->count() }}</div>
            <div class="stat-card__trend stat-card__trend--flat">Avec dépenses sur la période</div>
        </div>
        <div class="stat-card__icon stat-card__icon--blue">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/></svg>
        </div>
    </div>
</div>

{{-- Graphique + répartition --}}
<div class="expenses-layout">

    <div class="table-wrapper report-chart">
        <strong class="report-chart__title">Évolution mensuelle (12 mois)</strong>
        <canvas id="expChart" height="110"></canvas>
    </div>

    {{-- Répartition globale --}}
    <div class="table-wrapper">
        <div class="table-wrapper__header"><strong>Répartition des charges</strong></div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Poste</th>
                    <th class="text-right">Montant</th>
                    <th class="text-right">%</th>
                </tr>
            </thead>
            <tbody>
                {{-- Dépenses d'exploitation --}}
                <tr class="report-section-row">
                    <td colspan="3">Charges d'exploitation — {{ \App\Helpers\FormatHelper::money($totalExpenses) }}</td>
                </tr>
                @foreach($byCategory as $cat)
                @php $share = $total > 0 ? ($cat->expenses_sum_amount / $total * 100) : 0; @endphp
                <tr>
                    <td class="report-indent">{{ $cat->name }} <span class="report-table__meta-sm">({{ $cat->expenses_count }})</span></td>
                    <td class="report-table__amount-danger">{{ \App\Helpers\FormatHelper::money($cat->expenses_sum_amount) }}</td>
                    <td class="report-table__meta text-right">{{ number_format($share,1) }}%</td>
                </tr>
                @endforeach
                @if($uncategorized > 0)
                @php $share = $total > 0 ? ($uncategorized / $total * 100) : 0; @endphp
                <tr>
                    <td class="report-indent text-muted" style="font-style:italic;">Non catégorisées</td>
                    <td class="report-table__amount text-right text-warning font-600">{{ \App\Helpers\FormatHelper::money($uncategorized) }}</td>
                    <td class="report-table__meta text-right">{{ number_format($share,1) }}%</td>
                </tr>
                @endif
                @if($byCategory->isEmpty() && $uncategorized == 0)
                <tr><td colspan="3" class="report-table__empty--sm">Aucune dépense d'exploitation</td></tr>
                @endif

                {{-- Achats fournisseurs --}}
                <tr class="report-section-row report-section-row--blue">
                    <td colspan="3">Achats fournisseurs payés — {{ \App\Helpers\FormatHelper::money($totalPurchases) }}</td>
                </tr>
                @forelse($purchasesBySupplier as $p)
                @php $share = $total > 0 ? ($p->total / $total * 100) : 0; @endphp
                <tr>
                    <td class="report-indent">{{ $p->name }} <span class="report-table__meta-sm">({{ $p->nb }} bon{{ $p->nb > 1 ? 's' : '' }})</span></td>
                    <td class="report-table__amount-info">{{ \App\Helpers\FormatHelper::money($p->total) }}</td>
                    <td class="report-table__meta text-right">{{ number_format($share,1) }}%</td>
                </tr>
                @empty
                <tr><td colspan="3" class="report-table__empty--sm">Aucun achat fournisseur payé</td></tr>
                @endforelse

                {{-- Total --}}
                <tr class="tfoot-total">
                    <td>TOTAL CHARGES</td>
                    <td class="text-right c-tot">{{ \App\Helpers\FormatHelper::money($total) }}</td>
                    <td class="text-right text-sm">100%</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- Tableau mensuel --}}
<div class="table-wrapper mt-report">
    <div class="table-wrapper__header"><strong>Détail mensuel (12 mois)</strong></div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Mois</th>
                <th class="th-danger">Charges exploit.</th>
                <th class="th-info">Achats fourn.</th>
                <th style="text-align:right;color:#7C3AED;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($monthly as $m)
            @php $lineTotal = $m['expenses'] + $m['purchases']; @endphp
            <tr>
                <td><strong>{{ $m['label'] }}</strong></td>
                <td class="text-right @if($m['expenses']  > 0) c-exp @else c-dim @endif">
                    {{ $m['expenses']  > 0 ? \App\Helpers\FormatHelper::money($m['expenses'])  : '—' }}
                </td>
                <td class="text-right @if($m['purchases'] > 0) c-pur @else c-dim @endif">
                    {{ $m['purchases'] > 0 ? \App\Helpers\FormatHelper::money($m['purchases']) : '—' }}
                </td>
                <td class="text-right @if($lineTotal > 0) c-tot fw-600 @else c-dim @endif">
                    {{ $lineTotal > 0 ? \App\Helpers\FormatHelper::money($lineTotal) : '—' }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="tfoot-total">
                <td>TOTAL</td>
                <td class="text-right c-exp">{{ \App\Helpers\FormatHelper::money($totalExpenses) }}</td>
                <td class="text-right c-pur">{{ \App\Helpers\FormatHelper::money($totalPurchases) }}</td>
                <td class="text-right c-tot">{{ \App\Helpers\FormatHelper::money($total) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('expChart'), {
    type: 'bar',
    data: {
        labels: @json(collect($monthly)->pluck('label')),
        datasets: [
            {
                label: 'Charges exploit.',
                data: @json(collect($monthly)->pluck('expenses')),
                backgroundColor: 'rgba(239,68,68,.75)',
                borderRadius: 4,
                stack: 'charges',
            },
            {
                label: 'Achats fourn.',
                data: @json(collect($monthly)->pluck('purchases')),
                backgroundColor: 'rgba(59,130,246,.7)',
                borderRadius: 4,
                stack: 'charges',
            },
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: { callbacks: { label: c => c.dataset.label + ' : ' + new Intl.NumberFormat('fr-FR').format(c.parsed.y) + ' ' + window.CURRENCY } }
        },
        scales: {
            x: { stacked: true },
            y: { stacked: true, ticks: { callback: v => new Intl.NumberFormat('fr-FR', { notation: 'compact' }).format(v) + ' ' + window.CURRENCY } }
        }
    }
});
</script>
@endpush
