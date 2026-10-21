<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport de clôture caisse {{ $session->id }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1 { color: #14532d; font-size: 20px; margin: 0 0 5px; }
        h2 { color: #374151; font-size: 12px; margin: 20px 0 8px; }
        p { margin: 3px 0; }
        table { border-collapse: collapse; width: 100%; }
        .details td { padding: 4px 8px 4px 0; vertical-align: top; }
        .summary { margin: 14px 0 18px; }
        .summary td { background: #f3f4f6; border: 1px solid #d1d5db; padding: 9px; width: 25%; }
        .label { color: #6b7280; display: block; font-size: 8px; margin-bottom: 4px; text-transform: uppercase; }
        .value { font-size: 12px; font-weight: bold; }
        .sale { border: 1px solid #d1d5db; margin-bottom: 14px; page-break-inside: avoid; }
        .sale-header { background: #f3f4f6; padding: 8px; }
        .sale-header table td { padding: 2px 4px; }
        .items, .payments { table-layout: fixed; }
        .items th, .items td, .payments th, .payments td { border-bottom: 1px solid #e5e7eb; padding: 5px 7px; }
        .items th, .payments th { color: #4b5563; font-size: 8px; text-align: left; }
        .items th.right, .payments th.right { text-align: right; }
        .items th:nth-child(1), .items td:nth-child(1) { width: 46%; }
        .items th:nth-child(2), .items td:nth-child(2) { width: 12%; }
        .items th:nth-child(3), .items td:nth-child(3) { width: 21%; }
        .items th:nth-child(4), .items td:nth-child(4) { width: 21%; }
        .payments th:nth-child(1), .payments td:nth-child(1) { width: 30%; }
        .payments th:nth-child(2), .payments td:nth-child(2) { width: 16%; }
        .payments th:nth-child(3), .payments td:nth-child(3) { width: 34%; }
        .payments th:nth-child(4), .payments td:nth-child(4) { width: 20%; }
        .right { text-align: right; }
        .sale-total { background: #fafafa; padding: 7px 9px; text-align: right; }
        .muted { color: #6b7280; }
        .empty { border: 1px solid #d1d5db; color: #6b7280; padding: 12px; text-align: center; }
        .footer { border-top: 1px solid #d1d5db; color: #6b7280; font-size: 8px; margin-top: 20px; padding-top: 7px; }
    </style>
</head>
<body>
    <h1>Rapport de clôture de caisse</h1>
    <p class="muted">Généré le {{ now()->format('d/m/Y à H:i') }}</p>

    <h2>Session</h2>
    <table class="details">
        <tr>
            <td><strong>Caisse :</strong> {{ $session->caisse?->name ?? '—' }}</td>
            <td><strong>Caissier :</strong> {{ $session->user?->name ?? '—' }}</td>
            <td><strong>Entrepôt :</strong> {{ $session->warehouse?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong>Ouverture :</strong> {{ $session->opened_at?->format('d/m/Y H:i') ?? '—' }}</td>
            <td><strong>Clôture :</strong> {{ $session->closed_at?->format('d/m/Y H:i') ?? '—' }}</td>
            <td><strong>Transactions :</strong> {{ $session->sales->count() }}</td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td><span class="label">Fond d'ouverture</span><span class="value">{{ \App\Helpers\FormatHelper::money($session->opening_balance) }}</span></td>
            <td><span class="label">Total des ventes</span><span class="value">{{ \App\Helpers\FormatHelper::money($session->total_sales) }}</span></td>
            <td><span class="label">Montant encaissé</span><span class="value">{{ \App\Helpers\FormatHelper::money($session->sales->sum('amount_paid')) }}</span></td>
            <td><span class="label">Fond de fermeture</span><span class="value">{{ \App\Helpers\FormatHelper::money($session->closing_balance) }}</span></td>
        </tr>
    </table>

    <h2>Détail des transactions</h2>
    @forelse($session->sales as $sale)
        <div class="sale">
            <div class="sale-header">
                <table>
                    <tr>
                        <td><strong>{{ $sale->reference }}</strong></td>
                        <td>{{ $sale->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>Client : {{ $sale->customer?->name ?? 'Client comptoir' }}</td>
                        <td class="right">{{ ucfirst($sale->payment_status) }}</td>
                    </tr>
                </table>
            </div>
            <table class="items">
                <thead>
                    <tr>
                        <th>Désignation</th>
                        <th class="right">Quantité</th>
                        <th class="right">Prix unitaire</th>
                        <th class="right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $item)
                        <tr>
                            <td>{{ $item->item_name }}</td>
                            <td class="right">{{ $item->quantity }}</td>
                            <td class="right">{{ \App\Helpers\FormatHelper::money($item->unit_price) }}</td>
                            <td class="right">{{ \App\Helpers\FormatHelper::money($item->subtotal) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($sale->payments->isNotEmpty())
                <table class="payments">
                    <thead>
                        <tr>
                            <th>Règlement</th>
                            <th>Date</th>
                            <th>Mode</th>
                            <th class="right">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->payments as $payment)
                            <tr>
                                <td>{{ $payment->reference }}</td>
                                <td>{{ $payment->payment_date?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}{{ $payment->paymentAccount?->name ? ' - ' . $payment->paymentAccount->name : '' }}</td>
                                <td class="right">{{ \App\Helpers\FormatHelper::money($payment->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            <div class="sale-total">
                Total : <strong>{{ \App\Helpers\FormatHelper::money($sale->total) }}</strong>
                &nbsp; | &nbsp; Encaissé : <strong>{{ \App\Helpers\FormatHelper::money($sale->amount_paid) }}</strong>
                @if($sale->amount_due > 0)
                    &nbsp; | &nbsp; Reste dû : <strong>{{ \App\Helpers\FormatHelper::money($sale->amount_due) }}</strong>
                @endif
            </div>
        </div>
    @empty
        <div class="empty">Aucune transaction enregistrée pour cette session.</div>
    @endforelse

    <div class="footer">Rapport de session de caisse {{ $session->id }}.</div>
</body>
</html>