@extends('layouts.app')
@section('title', $supplier->name)
@section('breadcrumb')
    <a href="{{ route('suppliers.index') }}">Fournisseurs</a>
    <span class="sep">/</span><span class="current">{{ $supplier->name }}</span>
@endsection

@section('content')

<div class="page-header">
    <div class="page-header__title">
        <h2>{{ $supplier->name }}</h2>
        <p style="color:#64748b;">
            @if($supplier->company) {{ $supplier->company }} @endif
            @if($supplier->company && $supplier->phone) · @endif
            @if($supplier->phone) {{ $supplier->phone }} @endif
        </p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('suppliers.index') }}" class="btn btn--ghost">← Retour</a>
    </div>
</div>

{{-- Stat cards --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
    <div class="card" style="padding:20px;">
        <div style="font-size:11px;color:#94A3B8;text-transform:uppercase;font-weight:700;margin-bottom:6px;">Commandes</div>
        <div style="font-size:28px;font-weight:800;">{{ $stats->total_count }}</div>
        <div style="font-size:12px;color:#64748B;margin-top:4px;">achats enregistrés</div>
    </div>
    <div class="card" style="padding:20px;">
        <div style="font-size:11px;color:#94A3B8;text-transform:uppercase;font-weight:700;margin-bottom:6px;">Total achats</div>
        <div style="font-size:22px;font-weight:800;color:#3b82f6;">{{ \App\Helpers\FormatHelper::money($stats->total_achats) }}</div>
        <div style="font-size:12px;color:#64748B;margin-top:4px;">montant cumulé</div>
    </div>
    <div class="card" style="padding:20px;">
        <div style="font-size:11px;color:#94A3B8;text-transform:uppercase;font-weight:700;margin-bottom:6px;">Total payé</div>
        <div style="font-size:22px;font-weight:800;color:#16a34a;">{{ \App\Helpers\FormatHelper::money($stats->total_paid) }}</div>
        <div style="font-size:12px;color:#64748B;margin-top:4px;">règlements effectués</div>
    </div>
    <div class="card" style="padding:20px;">
        <div style="font-size:11px;color:#94A3B8;text-transform:uppercase;font-weight:700;margin-bottom:6px;">Dette fournisseur</div>
        <div style="font-size:22px;font-weight:800;color:{{ $stats->total_due > 0 ? '#ef4444' : '#94A3B8' }};">
            {{ \App\Helpers\FormatHelper::money($stats->total_due) }}
        </div>
        <div style="font-size:12px;color:#64748B;margin-top:4px;">reste à payer</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start;">

    {{-- Tableau des achats --}}
    <div class="table-wrapper">
        <div class="table-wrapper__header">
            <strong>Historique des achats</strong>
            <span style="font-size:13px;color:#64748B;">{{ $purchases->total() }} commande(s)</span>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Date</th>
                    <th>Articles</th>
                    <th>Total</th>
                    <th>Payé</th>
                    <th>Dette</th>
                    <th>Statut</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                @php $due = max(0, (float) $purchase->total - (float) $purchase->amount_paid); @endphp
                <tr>
                    <td>
                        <a href="{{ route('purchases.show', $purchase) }}" style="color:#3b82f6;font-weight:600;text-decoration:none;"
                           onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                            {{ $purchase->reference }}
                        </a>
                    </td>
                    <td style="font-size:13px;color:#64748B;">{{ \App\Helpers\FormatHelper::date($purchase->purchase_date) }}</td>
                    <td style="text-align:center;font-size:13px;">{{ $purchase->items_count ?? '—' }}</td>
                    <td><strong>{{ \App\Helpers\FormatHelper::money($purchase->total) }}</strong></td>
                    <td style="color:#16a34a;font-size:13px;">{{ \App\Helpers\FormatHelper::money($purchase->amount_paid) }}</td>
                    <td>
                        @if($due > 0)
                            <span style="color:#ef4444;font-weight:600;font-size:13px;">{{ \App\Helpers\FormatHelper::money($due) }}</span>
                        @else
                            <span style="color:#94a3b8;font-size:13px;">—</span>
                        @endif
                    </td>
                    <td>{!! \App\Helpers\FormatHelper::statusBadge($purchase->payment_status) !!}</td>
                    <td>
                        <div class="data-table__actions" style="justify-content:flex-end;">
                            <a href="{{ route('purchases.show', $purchase) }}" class="btn btn--ghost btn--sm btn--icon" title="Détail">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            </a>
                            <a href="{{ route('print.purchase', $purchase) }}" target="_blank" class="btn btn--ghost btn--sm btn--icon" title="Imprimer">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659"/></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:40px;color:#64748B;">Aucun achat pour ce fournisseur</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="table-wrapper__footer">
            <span>{{ $purchases->firstItem() ?? 0 }}–{{ $purchases->lastItem() ?? 0 }} sur {{ $purchases->total() }}</span>
            {{ $purchases->links() }}
        </div>
    </div>

    {{-- Fiche fournisseur --}}
    <div class="card" style="padding:20px;">
        <div style="font-size:11px;color:#94A3B8;text-transform:uppercase;font-weight:700;margin-bottom:14px;">Informations</div>
        <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;">
            <div style="display:flex;justify-content:space-between;gap:8px;">
                <span style="color:#64748B;flex-shrink:0;">Nom</span>
                <strong style="text-align:right;">{{ $supplier->name }}</strong>
            </div>
            @if($supplier->company)
            <div style="display:flex;justify-content:space-between;gap:8px;">
                <span style="color:#64748B;">Société</span>
                <span>{{ $supplier->company }}</span>
            </div>
            @endif
            @if($supplier->phone)
            <div style="display:flex;justify-content:space-between;gap:8px;">
                <span style="color:#64748B;">Téléphone</span>
                <span>{{ $supplier->phone }}</span>
            </div>
            @endif
            @if($supplier->email)
            <div style="display:flex;justify-content:space-between;gap:8px;">
                <span style="color:#64748B;">Email</span>
                <span style="font-size:12px;word-break:break-all;">{{ $supplier->email }}</span>
            </div>
            @endif
            @if($supplier->address)
            <div style="display:flex;justify-content:space-between;gap:8px;">
                <span style="color:#64748B;flex-shrink:0;">Adresse</span>
                <span style="text-align:right;">{{ $supplier->address }}</span>
            </div>
            @endif
            <div style="height:1px;background:#f1f5f9;"></div>
            <div style="display:flex;justify-content:space-between;">
                <span style="color:#64748B;">Statut</span>
                <span class="badge badge--{{ $supplier->is_active ? 'green' : 'gray' }}">
                    {{ $supplier->is_active ? 'Actif' : 'Inactif' }}
                </span>
            </div>
            @if($stats->total_due > 0)
            <div style="display:flex;justify-content:space-between;padding:8px 10px;background:#fef2f2;border-radius:6px;">
                <span style="color:#ef4444;font-weight:600;">Dette</span>
                <strong style="color:#ef4444;">{{ \App\Helpers\FormatHelper::money($stats->total_due) }}</strong>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
