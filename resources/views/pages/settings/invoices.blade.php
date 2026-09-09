@extends('layouts.app')
@section('title', 'Paramètres factures')
@section('breadcrumb')
    <a href="{{ route('settings.company') }}">Paramètres</a>
    <span class="sep">/</span><span class="current">Factures</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>Paramètres des factures</h2>
        <p>Personnaliser vos factures et bons de commande</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('settings.company') }}" class="btn btn--ghost">Retour entreprise</a>
    </div>
</div>

<div class="settings-grid">

    <div class="card settings-invoices__card">
        <h3 class="settings-invoices__title">Configuration des factures</h3>
        <form method="POST" action="{{ route('settings.invoices.save') }}">
            @csrf
            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label>Préfixe facture</label>
                    <input type="text" name="invoice_prefix" class="form-control" value="{{ $settings['invoice_prefix'] ?? 'FAC' }}" placeholder="FAC">
                    <span class="form-hint">Exemple : FAC-2026-0001</span>
                </div>
                <div class="form-group">
                    <label>Taux de taxe par défaut (%)</label>
                    <input type="number" name="tax_rate_default" min="0" max="100" step="0.01" class="form-control" value="{{ $settings['tax_rate_default'] ?? '18' }}">
                </div>
            </div>
            <div class="form-group">
                <label>Pied de page facture</label>
                <textarea name="invoice_footer" class="form-textarea" rows="3" placeholder="Merci pour votre confiance...">{{ $settings['invoice_footer'] ?? '' }}</textarea>
            </div>
            <div class="form-group">
                <label>Note par défaut sur facture</label>
                <textarea name="invoice_note" class="form-textarea" rows="2" placeholder="Conditions de vente...">{{ $settings['invoice_note'] ?? '' }}</textarea>
            </div>
            <div class="settings-invoices__checkboxes">
                <label class="form-checkbox">
                    <input type="checkbox" name="show_tax" value="1" {{ ($settings['show_tax'] ?? '1') ? 'checked' : '' }}>
                    Afficher la TVA sur les factures
                </label>
                <label class="form-checkbox">
                    <input type="checkbox" name="show_discount" value="1" {{ ($settings['show_discount'] ?? '0') ? 'checked' : '' }}>
                    Afficher les remises sur les factures
                </label>
                <label class="form-checkbox">
                    <input type="checkbox" name="show_logo" value="1" {{ ($settings['show_logo'] ?? '1') ? 'checked' : '' }}>
                    Afficher le logo sur les factures
                </label>
            </div>
            <button type="submit" class="btn btn--primary">Sauvegarder</button>
        </form>
    </div>

    <div class="card settings-invoices__card">
        <h3 class="settings-invoices__title--sm">Aperçu de la facture</h3>
        <div class="settings-invoices__preview">
            <div class="settings-invoices__preview-header">
                <div>
                    <div class="settings-invoices__preview-company-name">{{ \App\Models\Setting::get('company_name', config('app.name')) }}</div>
                    <div class="settings-invoices__preview-company-detail">{{ \App\Models\Setting::get('company_address', '') }}</div>
                    <div class="settings-invoices__preview-company-detail">{{ \App\Models\Setting::get('company_phone', '') }}</div>
                </div>
                <div class="settings-invoices__preview-invoice-right">
                    <div class="settings-invoices__preview-invoice-label">FACTURE</div>
                    <div class="settings-invoices__preview-invoice-meta">{{ $settings['invoice_prefix'] ?? 'FAC' }}-{{ date('Y') }}-0001</div>
                    <div class="settings-invoices__preview-invoice-meta">{{ date('d/m/Y') }}</div>
                </div>
            </div>
            <table class="settings-invoices__preview-table">
                <thead>
                    <tr>
                        <th>ARTICLE</th>
                        <th>QTÉ</th>
                        <th>TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Croissant</td><td>5</td><td>2 500 XOF</td></tr>
                    <tr><td>Pain au chocolat</td><td>3</td><td>1 800 XOF</td></tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2">TOTAL</td>
                        <td>4 300 XOF</td>
                    </tr>
                </tfoot>
            </table>
            @if($settings['invoice_footer'] ?? null)
                <div class="settings-invoices__preview-footer">{{ $settings['invoice_footer'] }}</div>
            @else
                <div class="settings-invoices__preview-footer settings-invoices__preview-footer--placeholder">
                    Pied de page de la facture
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
