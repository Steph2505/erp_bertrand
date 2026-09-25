@extends('layouts.app')
@section('title', 'Tableau de bord')

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>Bienvenue, {{ auth()->user()->name }}</h2>
        <p>Voici un résumé de l'activité du jour</p>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Ventes aujourd'hui</div>
            <div class="stat-card__value">{{ $todaySalesCount }}</div>
            <div class="stat-card__trend stat-card__trend--flat">Nombre de ventes enregistrées</div>
        </div>
        <div class="stat-card__icon stat-card__icon--green">
            <x-icon name="shopping-bag" />
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card__info">
            <div class="stat-card__label">Alertes stock</div>
            <div class="stat-card__value" style="{{ $lowStockProducts->count() > 0 ? 'color:#B45309;' : '' }}">{{ $lowStockProducts->count() }}</div>
            <div class="stat-card__trend {{ $lowStockProducts->count() > 0 ? 'stat-card__trend--down' : 'stat-card__trend--up' }}">
                {{ $lowStockProducts->count() > 0 ? 'Articles en stock faible' : 'Stocks OK' }}
            </div>
        </div>
        <div class="stat-card__icon stat-card__icon--yellow">
            <x-icon name="adjustments-horizontal" />
        </div>
    </div>
</div>

@if($quickLinks->isNotEmpty())
<div class="table-wrapper" style="padding:20px;margin-top:20px;">
    <strong style="display:block;margin-bottom:16px;">Accès rapide</strong>
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
        @foreach($quickLinks as $link)
        <a href="{{ route($link['route']) }}" class="btn btn--ghost" style="display:flex;align-items:center;gap:8px;">
            <x-icon name="{{ $link['icon'] }}" class="quick-link-icon" />
            {{ $link['label'] }}
        </a>
        @endforeach
    </div>
</div>
@endif

@if($lowStockProducts->isNotEmpty())
<div class="dashboard__alerts" style="margin-top:20px;">
    <div class="dashboard__alerts-header">
        <h3>⚠️ Stock faible</h3>
    </div>
    @foreach($lowStockProducts as $product)
        <div class="dashboard__alerts-item">
            <x-icon name="adjustments-horizontal" />
            <span class="dashboard__alerts-item-name">{{ $product->display_name }}</span>
            <span class="dashboard__alerts-item-stock">{{ $product->stock_quantity }} {{ $product->unit?->abbreviation ?? 'u.' }}</span>
        </div>
    @endforeach
</div>
@endif

<style>.quick-link-icon { width:16px;height:16px;flex-shrink:0; }</style>
@endsection
