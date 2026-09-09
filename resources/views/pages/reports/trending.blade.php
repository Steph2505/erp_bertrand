@extends('layouts.app')
@section('title', 'Produits en tendance')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Tendances</span>@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title"><h2>Produits en tendance</h2><p>Top 30 par chiffre d'affaires</p></div>
    <div class="page-header__actions">
        <div class="filter-tabs">
            <a href="{{ route('reports.trending') }}" class="filter-tabs__btn filter-tabs__btn--active">Tendances</a>
            <a href="{{ route('reports.items') }}" class="filter-tabs__btn">Articles</a>
            <a href="{{ route('reports.product-sale') }}" class="filter-tabs__btn">Vente produit</a>
            <a href="{{ route('reports.product-purchase') }}" class="filter-tabs__btn">Achat produit</a>
        </div>
    </div>
</div>

<form method="GET" class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0"><label>Du</label><input type="date" name="date_from" value="{{ $from }}" class="form-control"></div>
        <div class="form-group" style="margin:0"><label>Au</label><input type="date" name="date_to" value="{{ $to }}" class="form-control"></div>
        <button class="btn btn--primary">Filtrer</button>
        <a href="{{ route('reports.trending') }}" class="btn btn--ghost">Réinitialiser</a>
    </div>
</form>

@if($items->count())
<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">
    <div class="table-wrapper" style="padding:20px;"><canvas id="trendChart" height="120"></canvas></div>
    <div class="table-wrapper">
        <div class="table-wrapper__header"><strong>Podium</strong></div>
        @foreach($items->take(3) as $i => $item)
        <div style="padding:12px 16px;{{ $i < 2 ? 'border-bottom:1px solid #F1F5F9;' : '' }}">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:28px;height:28px;border-radius:50%;background:{{ ['#F59E0B','#94A3B8','#CD7F32'][$i] ?? '#E2E8F0' }};display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:white;">#{{ $i+1 }}</div>
                <div style="flex:1;">
                    <div style="font-weight:600;font-size:13px;">{{ $item->item_name }}</div>
                    <div style="font-size:11px;color:#64748B;">{{ \App\Helpers\FormatHelper::number($item->total_qty) }} unités — {{ $item->nb_sales }} ventes</div>
                </div>
                <div style="font-weight:700;color:#22C55E;font-size:13px;">{{ \App\Helpers\FormatHelper::money($item->total_revenue) }}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="table-wrapper" style="margin-top:20px;">
    <div class="table-wrapper__header"><strong>Classement complet</strong><span style="font-size:13px;color:#64748B;">{{ $items->count() }} article(s)</span></div>
    <table class="data-table">
        <thead><tr>
            <th>#</th><th>Article</th><th>Type</th>
            <th style="text-align:right">Qté vendue</th>
            <th style="text-align:right">Nb ventes</th>
            <th style="text-align:right">CA</th>
        </tr></thead>
        <tbody>
            @forelse($items as $i => $item)
            <tr>
                <td style="color:#64748B;font-weight:700;">#{{ $i+1 }}</td>
                <td><strong>{{ $item->item_name }}</strong></td>
                <td><span class="badge badge--{{ $item->item_type==='pack'?'pack':'gray' }}">{{ $item->item_type==='pack'?'Pack':'Produit' }}</span></td>
                <td style="text-align:right">{{ \App\Helpers\FormatHelper::number($item->total_qty) }}</td>
                <td style="text-align:right;color:#64748B;">{{ $item->nb_sales }}</td>
                <td style="text-align:right;font-weight:700;color:#22C55E;">{{ \App\Helpers\FormatHelper::money($item->total_revenue) }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center;padding:40px;color:#64748B;">Aucune vente sur la période</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
@if($items->count())
new Chart(document.getElementById('trendChart'), {
    type: 'bar',
    data: {
        labels: @json($items->take(10)->pluck('item_name')),
        datasets: [{ label: 'CA ({{ $currency }})', data: @json($items->take(10)->pluck('total_revenue')), backgroundColor:'rgba(76,187,23,.75)', borderRadius:5 }]
    },
    options: { indexAxis:'y', responsive:true, plugins:{ legend:{display:false}, tooltip:{callbacks:{label:c=>new Intl.NumberFormat('fr-FR').format(c.parsed.x)+' ' + window.CURRENCY}} }, scales:{ x:{ ticks:{callback:v=>new Intl.NumberFormat('fr-FR',{notation:'compact'}).format(v)} } } }
});
@endif
</script>
@endpush
