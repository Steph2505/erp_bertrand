@extends('layouts.app')
@section('title', 'Entreprise')
@section('breadcrumb')
    <a href="{{ route('settings.company') }}">Paramètres</a>
    <span class="sep">/</span><span class="current">Entreprise</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>Informations entreprise</h2>
        <p>Coordonnées et identité de votre établissement</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('settings.warehouses') }}" class="btn btn--ghost">Entrepôts</a>
        <a href="{{ route('settings.invoices') }}" class="btn btn--ghost">Factures</a>
        <a href="{{ route('settings.tax-rates') }}" class="btn btn--ghost">Taux de TVA</a>
    </div>
</div>

@if(session('success'))
<div class="alert alert--success" style="margin-bottom:16px;">{{ session('success') }}</div>
@endif

<div class="settings-grid">

    <div class="card settings-company__card">
        <h3 class="settings-company__section-title">Informations générales</h3>
        <form method="POST" action="{{ route('settings.company.save') }}">
            @csrf
            <div class="form-group">
                <label>Nom de l'établissement <span class="required">*</span></label>
                <input type="text" name="company_name" class="form-control"
                       value="{{ $settings['company_name'] ?? config('app.name') }}" required>
            </div>
            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="company_email" class="form-control"
                           value="{{ $settings['company_email'] ?? '' }}">
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="text" name="company_phone" class="form-control"
                           value="{{ $settings['company_phone'] ?? '' }}">
                </div>
            </div>
            <div class="form-group">
                <label>Adresse</label>
                <textarea name="company_address" class="form-textarea" rows="2">{{ $settings['company_address'] ?? '' }}</textarea>
            </div>
            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label>N° Fiscal / RCCM</label>
                    <input type="text" name="company_tax_id" class="form-control"
                           value="{{ $settings['company_tax_id'] ?? '' }}"
                           placeholder="Ex : RCCM-BJ-001-2020">
                </div>
                <div class="form-group">
                    <label>Site web</label>
                    <input type="url" name="company_website" class="form-control"
                           value="{{ $settings['company_website'] ?? '' }}"
                           placeholder="https://...">
                </div>
            </div>
            <div class="form-grid form-grid--2">
                <div class="form-group">
                    <label>Devise</label>
                    <input type="text" name="currency" class="form-control"
                           value="{{ $settings['currency'] ?? 'XOF' }}" placeholder="XOF">
                </div>
                <div class="form-group">
                    <label>Symbole devise</label>
                    <input type="text" name="currency_symbol" class="form-control"
                           value="{{ $settings['currency_symbol'] ?? 'XOF' }}" placeholder="XOF">
                </div>
            </div>
            <button type="submit" class="btn btn--primary">Sauvegarder</button>
        </form>
    </div>

    <div class="card settings-company__card">
        <h3 class="settings-company__section-title">Aperçu de l'entête</h3>
        <div class="settings-company__preview">
            <div class="settings-company__preview-name">
                {{ $settings['company_name'] ?? config('app.name') }}
            </div>
            @if($settings['company_address'] ?? null)
            <div class="settings-company__preview-line">{{ $settings['company_address'] }}</div>
            @endif
            @if($settings['company_phone'] ?? null)
            <div class="settings-company__preview-line">Tél : {{ $settings['company_phone'] }}</div>
            @endif
            @if($settings['company_email'] ?? null)
            <div class="settings-company__preview-line">{{ $settings['company_email'] }}</div>
            @endif
            @if($settings['company_tax_id'] ?? null)
            <div class="settings-company__preview-ref">Réf. : {{ $settings['company_tax_id'] }}</div>
            @endif
            <div class="settings-company__preview-footer">
                <span>Devise : <strong>{{ $settings['currency'] ?? 'XOF' }}</strong></span>
            </div>
        </div>

        <div class="settings-company__quick-links">
            <h3 class="settings-company__section-title--sm">Accès rapide</h3>
            <div class="settings-company__quick-links-list">
                <a href="{{ route('settings.warehouses') }}" class="btn btn--ghost settings-company__quick-link">
                    Gérer les entrepôts / sites
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="settings-company__quick-link-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
                <a href="{{ route('settings.invoices') }}" class="btn btn--ghost settings-company__quick-link">
                    Paramètres des factures
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="settings-company__quick-link-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
                <a href="{{ route('settings.tax-rates') }}" class="btn btn--ghost settings-company__quick-link">
                    Taux de TVA
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="settings-company__quick-link-icon"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
            </div>
        </div>
    </div>

</div>
@endsection
