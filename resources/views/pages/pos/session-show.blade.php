@extends('layouts.app')
@section('title', 'Session caisse')
@section('breadcrumb')
<a href="{{ route('pos.index') }}">POS</a>
<a href="{{ route('pos.sessions.index') }}">Sessions</a>
<span class="current">{{ $session->opened_at->format('d/m/Y') }}</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>Session du {{ $session->opened_at->format('d/m/Y') }}</h2>
        <p>
            {{ $session->user->name }}
            @if($session->caisse) — <strong>{{ $session->caisse->name }}</strong> @endif
            @if($session->warehouse) — {{ $session->warehouse->name }} @endif
        </p>
    </div>
    <div class="page-header__actions">
        @if($session->isOpen())
            <span class="badge badge--green" style="font-size:13px;padding:6px 14px;">Caisse ouverte</span>
            @if($session->user_id === auth()->id())
            <a href="{{ route('pos.index') }}" class="btn btn--primary">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/></svg>
                Continuer la caisse
            </a>
            @endif
        @else
            <span class="badge badge--gray" style="font-size:13px;padding:6px 14px;">Caisse fermée</span>
        @endif
    </div>
</div>

{{-- Stat cards --}}
<div class="stat-grid mb-24" style="grid-template-columns:repeat(4,1fr);">
    <div class="card card--padded">
        <div class="text-xs text-muted uppercase" style="letter-spacing:.04em;font-weight:700;margin-bottom:6px;">Fond d'ouverture</div>
        <div class="text-2xl font-800">{{ \App\Helpers\FormatHelper::money($session->opening_balance) }}</div>
        <div class="text-sm text-muted mt-4">{{ $session->opened_at->format('H:i') }}</div>
    </div>
    <div class="card card--padded">
        <div class="text-xs text-muted uppercase" style="letter-spacing:.04em;font-weight:700;margin-bottom:6px;">Total ventes</div>
        <div class="text-2xl font-800" style="color:#16a34a;">{{ \App\Helpers\FormatHelper::money($session->total_sales) }}</div>
        <div class="text-sm text-muted mt-4">{{ $session->sales->count() }} transaction(s)</div>
    </div>
    <div class="card card--padded">
        <div class="text-xs text-muted uppercase" style="letter-spacing:.04em;font-weight:700;margin-bottom:6px;">Attendu en caisse</div>
        <div class="text-2xl font-800" style="color:#3b82f6;">{{ \App\Helpers\FormatHelper::money($session->opening_balance + $session->total_sales) }}</div>
        <div class="text-sm text-muted mt-4">Fond + Ventes</div>
    </div>
    <div class="card card--padded">
        <div class="text-xs text-muted uppercase" style="letter-spacing:.04em;font-weight:700;margin-bottom:6px;">Fond de fermeture</div>
        @if($session->closing_balance !== null)
            @php $ecart = $session->closing_balance - ($session->opening_balance + $session->total_sales); @endphp
            <div class="text-2xl font-800 {{ $ecart == 0 ? 'text-success' : ($ecart > 0 ? 'text-info' : 'text-danger') }}">
                {{ \App\Helpers\FormatHelper::money($session->closing_balance) }}
            </div>
            <div class="text-sm mt-4 {{ $ecart >= 0 ? 'text-muted' : 'text-danger' }}">
                Écart : {{ $ecart >= 0 ? '+' : '' }}{{ \App\Helpers\FormatHelper::money($ecart) }}
            </div>
        @else
            <div class="text-2xl font-800 text-muted">—</div>
            <div class="text-sm text-muted mt-4">Caisse non clôturée</div>
        @endif
    </div>
</div>

@if($session->isOpen())
<div class="card mb-24" style="padding:20px;background:#fffbeb;border-color:#fde68a;">
    <h3 style="font-size:14px;font-weight:600;color:#92400e;margin-bottom:12px;">Clôturer la caisse</h3>
    <form method="POST" action="{{ route('pos.sessions.close', $session) }}" class="pos-close-panel__form">
        @csrf
        <div class="form-group" style="margin-bottom:0;">
            <label>Montant compté en caisse *</label>
            <input type="number" name="closing_balance" step="1" min="0" class="form-control pos-close-panel__input-balance" required
                placeholder="Entrer le montant">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label>Note</label>
            <input type="text" name="note" class="form-control pos-close-panel__input-note" placeholder="Observation (optionnel)">
        </div>
        <button type="submit" class="btn btn--danger" onclick="return confirm('Confirmer la clôture de caisse ?')">
            Clôturer la caisse
        </button>
    </form>
</div>
@endif

{{-- Liste des ventes --}}
<div class="table-wrapper">
    <div class="table-wrapper__header">
        <strong>Ventes de la session</strong>
        <span class="sale-index__header-total">
            {{ $session->sales->count() }} vente(s) — Total : <strong>{{ \App\Helpers\FormatHelper::money($session->total_sales) }}</strong>
        </span>
    </div>
    <table class="data-table">
        <thead><tr>
            <th>Référence</th>
            <th>Client</th>
            <th>Heure</th>
            <th>Total</th>
            <th>Statut</th>
            <th class="th-right">Actions</th>
        </tr></thead>
        <tbody>
            @forelse($session->sales as $sale)
            <tr>
                <td>
                    <a href="{{ route('pos.show-ref', $sale->reference) }}" class="pos-ref-link">
                        {{ $sale->reference }}
                    </a>
                </td>
                <td>{{ $sale->customer?->name ?? 'Comptoir' }}</td>
                <td class="text-muted text-md">{{ $sale->created_at->format('H:i') }}</td>
                <td><strong>{{ \App\Helpers\FormatHelper::money($sale->total) }}</strong></td>
                <td>{!! \App\Helpers\FormatHelper::statusBadge($sale->payment_status) !!}</td>
                <td>
                    <div class="data-table__actions">
                        <a href="{{ route('pos.receipt', $sale) }}" target="_blank"
                           class="btn btn--ghost btn--sm btn--icon" title="Imprimer le ticket">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659"/></svg>
                        </a>
                    </div>
                </td>
            </tr>
            @empty
            <tr class="sale-index__empty-row"><td colspan="6">Aucune vente dans cette session</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
