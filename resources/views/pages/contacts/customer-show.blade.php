@extends('layouts.app')
@section('title', $customer->name)
@section('breadcrumb')
    <a href="{{ route('customers.index') }}">Clients</a>
    <span class="sep">/</span><span class="current">{{ $customer->name }}</span>
@endsection

@section('content')

<div class="page-header">
    <div class="page-header__title">
        <h2>{{ $customer->name }}</h2>
        <p style="color:#64748b;">
            @if($customer->phone) {{ $customer->phone }} @endif
            @if($customer->phone && $customer->email) · @endif
            @if($customer->email) {{ $customer->email }} @endif
        </p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('customers.index') }}" class="btn btn--ghost">← Retour</a>
    </div>
</div>

{{-- Stat cards --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
    <div class="card" style="padding:20px;">
        <div style="font-size:11px;color:#94A3B8;text-transform:uppercase;font-weight:700;margin-bottom:6px;">Factures</div>
        <div style="font-size:28px;font-weight:800;">{{ $stats->total_count }}</div>
        <div style="font-size:12px;color:#64748B;margin-top:4px;">toutes factures</div>
    </div>
    <div class="card" style="padding:20px;">
        <div style="font-size:11px;color:#94A3B8;text-transform:uppercase;font-weight:700;margin-bottom:6px;">Chiffre d'affaires</div>
        <div style="font-size:22px;font-weight:800;color:#4CBB17;">{{ \App\Helpers\FormatHelper::money($stats->total_ca) }}</div>
        <div style="font-size:12px;color:#64748B;margin-top:4px;">total confirmé + payé</div>
    </div>
    <div class="card" style="padding:20px;">
        <div style="font-size:11px;color:#94A3B8;text-transform:uppercase;font-weight:700;margin-bottom:6px;">Total encaissé</div>
        <div style="font-size:22px;font-weight:800;color:#16a34a;">{{ \App\Helpers\FormatHelper::money($stats->total_paid) }}</div>
        <div style="font-size:12px;color:#64748B;margin-top:4px;">factures payées</div>
    </div>
    <div class="card" style="padding:20px;">
        <div style="font-size:11px;color:#94A3B8;text-transform:uppercase;font-weight:700;margin-bottom:6px;">Reste dû</div>
        <div style="font-size:22px;font-weight:800;color:{{ $stats->total_due > 0 ? '#ef4444' : '#94A3B8' }};">
            {{ \App\Helpers\FormatHelper::money($stats->total_due) }}
        </div>
        <div style="font-size:12px;color:#64748B;margin-top:4px;">factures confirmées impayées</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start;">

    {{-- Tableau des factures --}}
    <div class="table-wrapper">
        <div class="table-wrapper__header">
            <strong>Factures</strong>
            <span style="font-size:13px;color:#64748B;">{{ $sales->total() }} facture(s)</span>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Date</th>
                    <th>Articles</th>
                    <th>Total</th>
                    <th>Facture</th>
                    <th>Paiement</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                <tr>
                    <td>
                        <a href="{{ route('sales.show', $sale) }}" style="color:#4CBB17;font-weight:600;text-decoration:none;"
                           onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                            {{ $sale->reference }}
                        </a>
                    </td>
                    <td style="font-size:13px;color:#64748B;">{{ \App\Helpers\FormatHelper::date($sale->sale_date) }}</td>
                    <td style="text-align:center;font-size:13px;">{{ $sale->items_count ?? $sale->items->count() }}</td>
                    <td><strong>{{ \App\Helpers\FormatHelper::money($sale->total) }}</strong></td>
                    <td>{!! \App\Helpers\FormatHelper::statusBadge($sale->status) !!}</td>
                    <td>{!! \App\Helpers\FormatHelper::statusBadge($sale->payment_status) !!}</td>
                    <td>
                        <div class="data-table__actions" style="justify-content:flex-end;">
                            <a href="{{ route('sales.show', $sale) }}" class="btn btn--ghost btn--sm btn--icon" title="Détail">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            </a>
                            <a href="{{ route('print.sale', $sale) }}" target="_blank" class="btn btn--ghost btn--sm btn--icon" title="Imprimer">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659"/></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:40px;color:#64748B;">Aucune facture pour ce client</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="table-wrapper__footer">
            <span>{{ $sales->firstItem() ?? 0 }}–{{ $sales->lastItem() ?? 0 }} sur {{ $sales->total() }}</span>
            {{ $sales->links() }}
        </div>
    </div>

    {{-- Fiche client --}}
    <div class="card" style="padding:20px;">
        <div style="font-size:11px;color:#94A3B8;text-transform:uppercase;font-weight:700;margin-bottom:14px;">Informations</div>
        <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;">
            <div style="display:flex;justify-content:space-between;gap:8px;">
                <span style="color:#64748B;flex-shrink:0;">Nom</span>
                <strong style="text-align:right;">{{ $customer->name }}</strong>
            </div>
            @if($customer->phone)
            <div style="display:flex;justify-content:space-between;gap:8px;">
                <span style="color:#64748B;">Téléphone</span>
                <span>{{ $customer->phone }}</span>
            </div>
            @endif
            @if($customer->email)
            <div style="display:flex;justify-content:space-between;gap:8px;">
                <span style="color:#64748B;">Email</span>
                <span style="font-size:12px;word-break:break-all;">{{ $customer->email }}</span>
            </div>
            @endif
            @if($customer->address)
            <div style="display:flex;justify-content:space-between;gap:8px;">
                <span style="color:#64748B;flex-shrink:0;">Adresse</span>
                <span style="text-align:right;">{{ $customer->address }}</span>
            </div>
            @endif
            <div style="height:1px;background:#f1f5f9;"></div>
            <div style="display:flex;justify-content:space-between;">
                <span style="color:#64748B;">Statut</span>
                <span class="badge badge--{{ $customer->is_active ? 'green' : 'gray' }}">
                    {{ $customer->is_active ? 'Actif' : 'Inactif' }}
                </span>
            </div>
            @if($customer->opening_balance > 0)
            <div style="display:flex;justify-content:space-between;">
                <span style="color:#64748B;">Solde initial</span>
                <strong>{{ \App\Helpers\FormatHelper::money($customer->opening_balance) }}</strong>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
