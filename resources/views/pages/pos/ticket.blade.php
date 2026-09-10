@extends('layouts.app')
@section('title', 'Ticket ' . $sale->reference)
@section('breadcrumb')
<a href="{{ route('pos.list') }}">Ventes POS</a>
<span class="current">{{ $sale->reference }}</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>{{ $sale->reference }}</h2>
        <p>{{ $sale->created_at->format('d/m/Y à H:i') }}</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('pos.receipt', $sale) }}" target="_blank" class="btn btn--ghost">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659"/></svg>
            Imprimer le ticket
        </a>
        <a href="{{ route('pos.list') }}" class="btn btn--ghost">Retour</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">

    {{-- Colonne principale : articles --}}
    <div>
        <div class="card" style="padding:0;overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0;background:#f8fafc;">
                <strong style="font-size:14px;">Articles</strong>
            </div>
            <table class="data-table" style="margin:0;">
                <thead>
                    <tr>
                        <th>Désignation</th>
                        <th style="text-align:center;width:70px;">Qté</th>
                        <th style="text-align:right;width:130px;">Prix unitaire</th>
                        <th style="text-align:right;width:130px;">Sous-total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $item)
                    <tr>
                        <td>
                            <span style="font-weight:500;">{{ $item->item_name }}</span>
                            @if($item->item_type === 'pack')
                                <span style="font-size:11px;background:#eff6ff;color:#3b82f6;font-weight:600;padding:2px 6px;border-radius:4px;margin-left:6px;">Pack</span>
                            @endif
                        </td>
                        <td style="text-align:center;">{{ $item->quantity }}</td>
                        <td style="text-align:right;color:#64748b;">{{ \App\Helpers\FormatHelper::money($item->unit_price) }}</td>
                        <td style="text-align:right;font-weight:600;">{{ \App\Helpers\FormatHelper::money($item->subtotal) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot style="background:#f8fafc;border-top:2px solid #e2e8f0;">
                    @if($sale->discount > 0)
                    <tr>
                        <td colspan="3" style="text-align:right;color:#64748b;padding:8px 16px;">Sous-total</td>
                        <td style="text-align:right;padding:8px 16px;">{{ \App\Helpers\FormatHelper::money($sale->subtotal) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align:right;color:#ef4444;padding:4px 16px;">Remise</td>
                        <td style="text-align:right;color:#ef4444;padding:4px 16px;">−{{ \App\Helpers\FormatHelper::money($sale->discount) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td colspan="3" style="text-align:right;font-weight:700;font-size:15px;padding:12px 16px;">Total</td>
                        <td style="text-align:right;font-weight:800;font-size:17px;color:#1749B3;padding:12px 16px;">
                            {{ \App\Helpers\FormatHelper::money($sale->total) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Colonne latérale : infos + paiement --}}
    <div style="display:flex;flex-direction:column;gap:16px;">

        {{-- Infos ticket --}}
        <div class="card" style="padding:20px;">
            <div style="font-size:11px;color:#94a3b8;text-transform:uppercase;font-weight:700;margin-bottom:14px;">Informations</div>
            <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;">
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#64748b;">Référence</span>
                    <strong style="color:#1749B3;">{{ $sale->reference }}</strong>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#64748b;">Date</span>
                    <span>{{ $sale->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#64748b;">Client</span>
                    <span>{{ $sale->customer?->name ?? 'Client comptoir' }}</span>
                </div>
                @if($sale->posSession?->caisse)
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#64748b;">Caisse</span>
                    <span>{{ $sale->posSession->caisse->name }}</span>
                </div>
                @endif
                @if($sale->createdBy)
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#64748b;">Caissier</span>
                    <span>{{ $sale->createdBy->name }}</span>
                </div>
                @endif
                @if($sale->warehouse)
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#64748b;">Site</span>
                    <span>{{ $sale->warehouse->name }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Paiement --}}
        <div class="card" style="padding:20px;">
            <div style="font-size:11px;color:#94a3b8;text-transform:uppercase;font-weight:700;margin-bottom:14px;">Paiement</div>
            <div style="display:flex;flex-direction:column;gap:10px;font-size:13px;">
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#64748b;">Total</span>
                    <strong>{{ \App\Helpers\FormatHelper::money($sale->total) }}</strong>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#64748b;">Montant reçu</span>
                    <span style="color:#16a34a;font-weight:600;">{{ \App\Helpers\FormatHelper::money($sale->amount_paid) }}</span>
                </div>
                @if($sale->amount_due > 0)
                <div style="display:flex;justify-content:space-between;padding:8px 10px;background:#fef2f2;border-radius:6px;">
                    <span style="color:#ef4444;font-weight:600;">Reste dû</span>
                    <strong style="color:#ef4444;">{{ \App\Helpers\FormatHelper::money($sale->amount_due) }}</strong>
                </div>
                @else
                <div style="display:flex;justify-content:space-between;padding:8px 10px;background:#f0fdf4;border-radius:6px;">
                    <span style="color:#16a34a;font-weight:600;">Monnaie rendue</span>
                    <strong style="color:#16a34a;">{{ \App\Helpers\FormatHelper::money($sale->amount_paid - $sale->total) }}</strong>
                </div>
                @endif
                <div style="display:flex;justify-content:space-between;padding-top:6px;border-top:1px solid #f1f5f9;">
                    <span style="color:#64748b;">Statut</span>
                    {!! \App\Helpers\FormatHelper::statusBadge($sale->payment_status) !!}
                </div>
            </div>
        </div>

        @if($sale->note)
        <div class="card" style="padding:16px 20px;">
            <div style="font-size:11px;color:#94a3b8;text-transform:uppercase;font-weight:700;margin-bottom:8px;">Note</div>
            <p style="font-size:13px;color:#475569;">{{ $sale->note }}</p>
        </div>
        @endif

    </div>
</div>
@endsection
