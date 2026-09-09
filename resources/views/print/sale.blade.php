<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture {{ $sale->reference }}</title>
    <style>
        * { margin:0;padding:0;box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial,sans-serif;font-size:13px;color:#1e293b;background:#fff; }
        .page { max-width:800px;margin:0 auto;padding:40px; }
        .header { display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:40px; }
        .company-name { font-size:22px;font-weight:800;color:#1e293b;margin-bottom:4px; }
        .company-info { font-size:12px;color:#64748B;line-height:1.7; }
        .invoice-label { font-size:30px;font-weight:900;color:#4CBB17;letter-spacing:-.5px; }
        .invoice-meta { text-align:right;font-size:13px;color:#64748B;margin-top:6px; }
        .invoice-meta strong { color:#1e293b; }
        .divider { height:2px;background:linear-gradient(to right,#4CBB17,#22c55e);margin:24px 0;border-radius:2px; }
        .parties { display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:30px; }
        .party-box { background:#f8fafc;border-radius:8px;padding:16px; }
        .party-label { font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94A3B8;margin-bottom:8px; }
        .party-name { font-size:15px;font-weight:700;color:#1e293b;margin-bottom:4px; }
        .party-info { font-size:12px;color:#64748B;line-height:1.6; }
        table { width:100%;border-collapse:collapse;margin-bottom:24px; }
        thead { background:#f1f5f9; }
        th { padding:10px 14px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748B; }
        th:last-child { text-align:right; }
        td { padding:12px 14px;border-bottom:1px solid #f1f5f9;font-size:13px; }
        td:last-child { text-align:right;font-weight:600; }
        tbody tr:last-child td { border-bottom:none; }
        .totals { display:flex;justify-content:flex-end;margin-bottom:30px; }
        .totals-box { width:260px; }
        .totals-row { display:flex;justify-content:space-between;padding:6px 0;font-size:13px; }
        .totals-row.total { border-top:2px solid #e2e8f0;margin-top:6px;padding-top:10px;font-size:16px;font-weight:800; }
        .totals-row.total span:last-child { color:#4CBB17; }
        .badge { display:inline-block;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:600; }
        .badge-green { background:#dcfce7;color:#16a34a; }
        .badge-yellow { background:#fef9c3;color:#ca8a04; }
        .badge-red { background:#fee2e2;color:#dc2626; }
        .footer { border-top:1px solid #e2e8f0;padding-top:20px;text-align:center;color:#94A3B8;font-size:11px; }
        @media print {
            body { print-color-adjust:exact;-webkit-print-color-adjust:exact; }
            .no-print { display:none!important; }
        }
    </style>
</head>
<body>
<div class="page">

    {{-- Bouton impression --}}
    <div class="no-print" style="text-align:right;margin-bottom:20px;display:flex;gap:8px;justify-content:flex-end;">
        <button onclick="window.print()" style="padding:10px 20px;background:#4CBB17;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;">
            🖨️ Imprimer
        </button>
        <button onclick="window.close()" style="padding:10px 20px;background:#f1f5f9;color:#1e293b;border:none;border-radius:8px;cursor:pointer;font-size:13px;">
            Fermer
        </button>
    </div>

    <div class="header">
        <div>
            <div class="company-name">{{ \App\Models\Setting::get('company_name', config('app.name')) }}</div>
            <div class="company-info">
                {{ \App\Models\Setting::get('company_address', '') }}<br>
                {{ \App\Models\Setting::get('company_phone', '') }}<br>
                {{ \App\Models\Setting::get('company_email', '') }}
            </div>
        </div>
        <div>
            <div class="invoice-label">FACTURE</div>
            <div class="invoice-meta">
                N° <strong>{{ $sale->reference }}</strong><br>
                Date : <strong>{{ \App\Helpers\FormatHelper::date($sale->sale_date) }}</strong><br>
                Statut : @php
                    $badgeClass = match($sale->payment_status) { 'paid' => 'badge-green', 'partial' => 'badge-yellow', default => 'badge-red' };
                    $badgeLabel = match($sale->payment_status) { 'paid' => 'Payé', 'partial' => 'Partiel', default => 'En attente' };
                @endphp
                <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <div class="parties">
        <div class="party-box">
            <div class="party-label">Vendeur</div>
            <div class="party-name">{{ \App\Models\Setting::get('company_name', config('app.name')) }}</div>
            <div class="party-info">
                {{ \App\Models\Setting::get('company_address', '') }}<br>
                RCCM : {{ \App\Models\Setting::get('company_tax_id', '—') }}
            </div>
        </div>
        <div class="party-box">
            <div class="party-label">Client</div>
            <div class="party-name">{{ $sale->customer?->name ?? 'Client comptoir' }}</div>
            <div class="party-info">
                @if($sale->customer?->phone) Tél : {{ $sale->customer->phone }}<br> @endif
                @if($sale->customer?->email) {{ $sale->customer->email }}<br> @endif
                @if($sale->customer?->address) {{ $sale->customer->address }} @endif
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:40px">#</th>
                <th>Désignation</th>
                <th style="text-align:center;width:80px">Qté</th>
                <th style="text-align:right;width:130px">Prix unit.</th>
                <th style="text-align:right;width:130px">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $i => $item)
                <tr>
                    <td style="color:#94A3B8;">{{ $i + 1 }}</td>
                    <td>
                        {{ $item->item_name }}
                        @if($item->item_type === 'pack')
                            <span class="badge badge-{{ 'blue' }}" style="background:#dbeafe;color:#1d4ed8;">Pack</span>
                        @endif
                    </td>
                    <td style="text-align:center;">{{ $item->quantity }}</td>
                    <td style="text-align:right;">{{ \App\Helpers\FormatHelper::money($item->unit_price) }}</td>
                    <td style="text-align:right;">{{ \App\Helpers\FormatHelper::money($item->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-box">
            <div class="totals-row">
                <span>Sous-total</span>
                <span>{{ \App\Helpers\FormatHelper::money($sale->subtotal) }}</span>
            </div>
            @if($sale->discount > 0)
            <div class="totals-row">
                <span>Remise</span>
                <span>-{{ \App\Helpers\FormatHelper::money($sale->discount) }}</span>
            </div>
            @endif
            <div class="totals-row total">
                <span>TOTAL</span>
                <span>{{ \App\Helpers\FormatHelper::money($sale->total) }}</span>
            </div>
            @if($sale->amount_paid > 0)
            <div class="totals-row" style="margin-top:6px;color:#64748B;">
                <span>Payé</span>
                <span>{{ \App\Helpers\FormatHelper::money($sale->amount_paid) }}</span>
            </div>
            @if($sale->amount_due > 0)
            <div class="totals-row" style="color:#ef4444;">
                <span>Reste dû</span>
                <span>{{ \App\Helpers\FormatHelper::money($sale->amount_due) }}</span>
            </div>
            @endif
            @endif
        </div>
    </div>

    @if($sale->note)
    <div style="background:#f8fafc;border-radius:8px;padding:14px;margin-bottom:24px;font-size:12px;color:#64748B;">
        <strong>Note :</strong> {{ $sale->note }}
    </div>
    @endif

    <div class="footer">
        @if(\App\Models\Setting::get('invoice_footer'))
            <p>{{ \App\Models\Setting::get('invoice_footer') }}</p>
        @else
            <p>Merci pour votre confiance — {{ \App\Models\Setting::get('company_name', config('app.name')) }}</p>
        @endif
        <p style="margin-top:6px;">Document généré le {{ now()->format('d/m/Y à H:i') }} — ERP Bertrand Store</p>
    </div>
</div>
</body>
</html>
