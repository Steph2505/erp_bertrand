@extends('layouts.app')
@section('title', 'Retours achats')
@section('breadcrumb')
    <a href="{{ route('purchases.index') }}">Achats</a>
    <span class="sep">/</span><span class="current">Retours</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>Retours achats</h2>
        <p>{{ $returns->total() }} retour(s) enregistré(s)</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('purchase-returns.create') }}" class="btn btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nouveau retour
        </a>
    </div>
</div>

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Référence</th>
                <th>Achat lié</th>
                <th>Fournisseur</th>
                <th>Date retour</th>
                <th class="purchase-returns__th-amount">Montant</th>
                <th>Motif</th>
                <th>Créé par</th>
            </tr>
        </thead>
        <tbody>
            @forelse($returns as $ret)
            <tr>
                <td class="purchase-returns__ref">{{ $ret->reference }}</td>
                <td>
                    <a href="{{ route('purchases.show', $ret->purchase) }}" class="purchase-returns__purchase-link">
                        {{ $ret->purchase->reference }}
                    </a>
                </td>
                <td>{{ $ret->purchase->supplier?->name ?? '—' }}</td>
                <td class="purchase-returns__date">{{ \App\Helpers\FormatHelper::date($ret->return_date) }}</td>
                <td class="purchase-returns__amount">{{ \App\Helpers\FormatHelper::money($ret->total) }}</td>
                <td class="purchase-returns__reason">{{ $ret->reason ?? '—' }}</td>
                <td class="purchase-returns__creator">{{ $ret->createdBy?->name ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="purchase-returns__empty">Aucun retour enregistré</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="table-wrapper__footer">
        <span>{{ $returns->firstItem() ?? 0 }}–{{ $returns->lastItem() ?? 0 }} sur {{ $returns->total() }}</span>
        {{ $returns->withQueryString()->links() }}
    </div>
</div>
@endsection
