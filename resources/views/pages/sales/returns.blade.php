@extends('layouts.app')
@section('title', 'Retours ventes')
@section('breadcrumb')<a href="{{ route('sales.index') }}">Ventes</a> <span class="current">Retours</span>@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>Retours ventes</h2>
        <p>{{ $returns->total() }} retour(s) enregistré(s)</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('sale-returns.create') }}" class="btn btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nouveau retour
        </a>
    </div>
</div>

<div class="table-wrapper">
    <table class="data-table">
        <thead><tr>
            <th>Référence</th>
            <th>Vente liée</th>
            <th>Date retour</th>
            <th>Montant retourné</th>
            <th>Motif</th>
            <th>Créé par</th>
        </tr></thead>
        <tbody>
            @forelse($returns as $ret)
            <tr>
                <td class="sale-returns__ref">{{ $ret->reference }}</td>
                <td>
                    <a href="{{ route('sales.show', $ret->sale) }}" class="sale-returns__sale-link">
                        {{ $ret->sale->reference }}
                    </a>
                </td>
                <td class="sale-returns__date">{{ \App\Helpers\FormatHelper::date($ret->return_date) }}</td>
                <td><strong class="sale-returns__amount">{{ \App\Helpers\FormatHelper::money($ret->total) }}</strong></td>
                <td class="sale-returns__meta">{{ $ret->reason ?? '—' }}</td>
                <td class="sale-returns__meta">{{ $ret->createdBy?->name ?? '—' }}</td>
            </tr>
            @empty
            <tr class="sale-returns__empty-row"><td colspan="6">Aucun retour enregistré</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="table-wrapper__footer">
        <span>{{ $returns->firstItem() ?? 0 }}–{{ $returns->lastItem() ?? 0 }} sur {{ $returns->total() }}</span>
        {{ $returns->withQueryString()->links() }}
    </div>
</div>
@endsection
