@extends('layouts.app')
@section('title', 'Achat & Vente')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Achat & Vente</span>@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title"><h2>Achat & Vente</h2><p>Comparatif annuel {{ $year }}</p></div>
    <div class="page-header__actions">
        <form method="GET" style="display:flex;gap:8px;">
            <select name="year" class="form-select" style="width:auto" onchange="this.form.submit()">
                @foreach($years as $y)<option value="{{ $y }}" {{ $y==$year?'selected':''}}>{{ $y }}</option>@endforeach
            </select>
        </form>
    </div>
</div>

<div class="stat-grid" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-card__info"><div class="stat-card__label">CA {{ $year }}</div><div class="stat-card__value" style="color:#22C55E">{{ \App\Helpers\FormatHelper::money($totals['sales']) }}</div><div class="stat-card__trend stat-card__trend--flat">Ventes confirmées</div></div>
        <div class="stat-card__icon stat-card__icon--green"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/></svg></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info"><div class="stat-card__label">Achats {{ $year }}</div><div class="stat-card__value" style="color:#3B82F6">{{ \App\Helpers\FormatHelper::money($totals['purchases']) }}</div><div class="stat-card__trend stat-card__trend--flat">Achats confirmés</div></div>
        <div class="stat-card__icon stat-card__icon--blue"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/></svg></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__info"><div class="stat-card__label">Marge brute {{ $year }}</div>
            <div class="stat-card__value" style="color:{{ $totals['margin']>=0?'#22C55E':'#EF4444' }}">{{ \App\Helpers\FormatHelper::money($totals['margin']) }}</div>
            <div class="stat-card__trend stat-card__trend--{{ $totals['margin']>=0?'up':'down' }}">CA – Achats</div></div>
        <div class="stat-card__icon stat-card__icon--{{ $totals['margin']>=0?'green':'red' }}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/></svg></div>
    </div>
</div>

<div class="table-wrapper" style="padding:20px;margin-bottom:20px;"><canvas id="psChart" height="80"></canvas></div>

<div class="table-wrapper">
    <div class="table-wrapper__header"><strong>Détail mensuel {{ $year }}</strong></div>
    <table class="data-table">
        <thead><tr>
            <th>Mois</th>
            <th style="text-align:right;color:#22C55E;">Ventes</th>
            <th style="text-align:right;color:#3B82F6;">Achats</th>
            <th style="text-align:right">Marge</th>
        </tr></thead>
        <tbody>
            @foreach($monthly as $m)
            <tr>
                <td><strong>{{ $m['label'] }}</strong></td>
                <td style="text-align:right">{{ \App\Helpers\FormatHelper::money($m['sales']) }}</td>
                <td style="text-align:right">{{ \App\Helpers\FormatHelper::money($m['purchases']) }}</td>
                <td style="text-align:right;font-weight:600;color:{{ $m['margin']>=0?'#22C55E':'#EF4444' }}">
                    {{ $m['margin']>=0?'+':'' }}{{ \App\Helpers\FormatHelper::money($m['margin']) }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="border-top:2px solid #E2E8F0;background:#F8FAFC;font-weight:700;">
                <td>TOTAL</td>
                <td style="text-align:right;color:#22C55E;">{{ \App\Helpers\FormatHelper::money($totals['sales']) }}</td>
                <td style="text-align:right;color:#3B82F6;">{{ \App\Helpers\FormatHelper::money($totals['purchases']) }}</td>
                <td style="text-align:right;color:{{ $totals['margin']>=0?'#22C55E':'#EF4444' }}">{{ \App\Helpers\FormatHelper::money($totals['margin']) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('psChart'), {
    type: 'bar',
    data: {
        labels: @json(collect($monthly)->pluck('label')),
        datasets: [
            { label: 'Ventes', data: @json(collect($monthly)->pluck('sales')), backgroundColor:'rgba(34,197,94,.75)', borderRadius:5 },
            { label: 'Achats', data: @json(collect($monthly)->pluck('purchases')), backgroundColor:'rgba(59,130,246,.65)', borderRadius:5 },
            { label: 'Marge', data: @json(collect($monthly)->pluck('margin')), type:'line', borderColor:'#F59E0B', backgroundColor:'rgba(245,158,11,.1)', borderWidth:2, pointRadius:4, tension:.3, fill:false },
        ]
    },
    options: { responsive:true, plugins:{ legend:{position:'top'}, tooltip:{callbacks:{label: c=>c.dataset.label+' : '+new Intl.NumberFormat('fr-FR').format(c.parsed.y)+' ' + window.CURRENCY}} }, scales:{y:{ticks:{callback:v=>new Intl.NumberFormat('fr-FR',{notation:'compact'}).format(v)+' ' + window.CURRENCY}}} }
});
</script>
@endpush
