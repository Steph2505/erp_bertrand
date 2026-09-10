<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket {{ $sale->reference }}</title>
    <style>
        * { margin:0;padding:0;box-sizing:border-box; }
        body { font-family:'Courier New',Courier,monospace;font-size:12px;color:#000;background:#fff; }
        .receipt { width:300px;margin:0 auto;padding:20px 10px; }
        .center { text-align:center; }
        .company-name { font-size:16px;font-weight:700;margin-bottom:2px; }
        .company-sub { font-size:10px;color:#444;line-height:1.5; }
        .divider-dashed { border:none;border-top:1px dashed #000;margin:10px 0; }
        .divider-solid { border:none;border-top:1px solid #000;margin:10px 0; }
        .meta { font-size:11px;line-height:1.8; }
        .meta-row { display:flex;justify-content:space-between; }
        table { width:100%;border-collapse:collapse;font-size:11px; }
        th { text-align:left;font-weight:700;padding:3px 0;border-bottom:1px solid #000; }
        th:last-child { text-align:right; }
        td { padding:4px 0;vertical-align:top; }
        td:last-child { text-align:right;white-space:nowrap;padding-left:8px; }
        .item-name { font-size:11px;line-height:1.4; }
        .item-detail { font-size:10px;color:#555; }
        .totals { font-size:12px; }
        .totals-row { display:flex;justify-content:space-between;padding:3px 0; }
        .totals-row.grand { font-size:14px;font-weight:700;border-top:1px solid #000;border-bottom:1px solid #000;padding:6px 0;margin:4px 0; }
        .totals-row.change { font-size:13px;font-weight:700; }
        .badge { font-size:10px;font-weight:700;text-transform:uppercase; }
        .footer-text { font-size:10px;color:#444;line-height:1.6; }
        .barcode-placeholder { font-size:9px;color:#999;border:1px dashed #ccc;padding:6px;margin-top:8px; }
        @media print {
            body { print-color-adjust:exact;-webkit-print-color-adjust:exact; }
            .no-print { display:none!important; }
            .receipt { width:100%; }
        }
        @page { size: 80mm auto; margin: 5mm; }
    </style>
</head>
<body>

<div class="no-print" style="text-align:center;padding:16px;background:#f1f5f9;margin-bottom:0;">
    <button onclick="window.print()" style="padding:8px 20px;background:#1749B3;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;margin-right:8px;">
        🖨️ Imprimer
    </button>
    <button onclick="window.close()" style="padding:8px 16px;background:#e2e8f0;color:#0E1726;border:none;border-radius:6px;cursor:pointer;font-size:13px;">
        Fermer
    </button>
</div>

<div class="receipt">

    {{-- En-tête --}}
    <div class="center">
        <div class="company-name">{{ \App\Models\Setting::get('company_name', config('app.name')) }}</div>
        <div class="company-sub">
            {{ \App\Models\Setting::get('company_address', '') }}<br>
            Tél : {{ \App\Models\Setting::get('company_phone', '') }}
        </div>
    </div>

    <hr class="divider-dashed">

    <div class="meta">
        <div class="meta-row"><span>Ticket N°</span><span><strong>{{ $sale->reference }}</strong></span></div>
        <div class="meta-row"><span>Date</span><span>{{ $sale->created_at->format('d/m/Y H:i') }}</span></div>
        <div class="meta-row"><span>Client</span><span>{{ $sale->customer?->name ?? 'Comptoir' }}</span></div>
        @if($sale->posSession?->caisse)
        <div class="meta-row"><span>Caisse</span><span>{{ $sale->posSession->caisse->name }}</span></div>
        @endif
        @if($sale->createdBy)
        <div class="meta-row"><span>Caissier</span><span>{{ $sale->createdBy->name }}</span></div>
        @endif
        @if($sale->warehouse)
        <div class="meta-row"><span>Site</span><span>{{ $sale->warehouse->name }}</span></div>
        @endif
    </div>

    <hr class="divider-solid">

    {{-- Articles --}}
    <table>
        <thead>
            <tr>
                <th>Désignation</th>
                <th style="text-align:center;width:30px">Qté</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td>
                    <div class="item-name">{{ $item->item_name }}</div>
                    <div class="item-detail">{{ \App\Helpers\FormatHelper::money($item->unit_price) }} × {{ $item->quantity }}</div>
                </td>
                <td style="text-align:center;">{{ $item->quantity }}</td>
                <td>{{ \App\Helpers\FormatHelper::money($item->subtotal) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <hr class="divider-dashed">

    {{-- Totaux --}}
    <div class="totals">
        @if($sale->discount > 0)
        <div class="totals-row">
            <span>Sous-total</span>
            <span>{{ \App\Helpers\FormatHelper::money($sale->subtotal) }}</span>
        </div>
        <div class="totals-row">
            <span>Remise</span>
            <span>-{{ \App\Helpers\FormatHelper::money($sale->discount) }}</span>
        </div>
        @endif
        <div class="totals-row grand">
            <span>TOTAL</span>
            <span>{{ \App\Helpers\FormatHelper::money($sale->total) }}</span>
        </div>
        @if($sale->amount_paid > 0)
        <div class="totals-row">
            <span>Reçu</span>
            <span>{{ \App\Helpers\FormatHelper::money($sale->amount_paid) }}</span>
        </div>
        @if($sale->amount_paid >= $sale->total)
        <div class="totals-row change">
            <span>Monnaie</span>
            <span>{{ \App\Helpers\FormatHelper::money($sale->amount_paid - $sale->total) }}</span>
        </div>
        @else
        <div class="totals-row" style="font-weight:600;">
            <span>Reste dû</span>
            <span>{{ \App\Helpers\FormatHelper::money($sale->amount_due) }}</span>
        </div>
        @endif
        @endif
    </div>

    {{-- Statut paiement --}}
    <div class="center" style="margin-top:10px;">
        @php
            $label = match($sale->payment_status) { 'paid' => '✓ PAYÉ', 'partial' => '~ PARTIEL', default => '✗ EN ATTENTE' };
        @endphp
        <span class="badge">{{ $label }}</span>
    </div>

    <hr class="divider-dashed">

    {{-- Pied de ticket --}}
    <div class="center footer-text">
        @if(\App\Models\Setting::get('invoice_footer'))
            <p>{{ \App\Models\Setting::get('invoice_footer') }}</p>
        @else
            <p>Merci pour votre achat !<br>À bientôt chez {{ \App\Models\Setting::get('company_name', config('app.name')) }}</p>
        @endif
        <p style="margin-top:8px;font-size:9px;color:#aaa;">{{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

</div>
</body>
</html>
