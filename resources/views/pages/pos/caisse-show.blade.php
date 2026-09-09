@extends('layouts.app')
@section('title', $caisse->name)
@section('breadcrumb')
    <a href="{{ route('pos.index') }}">POS</a>
    <span class="sep">/</span>
    <a href="{{ route('pos.caisses.index') }}">Caisses</a>
    <span class="sep">/</span><span class="current">{{ $caisse->name }}</span>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>{{ $caisse->name }}</h2>
        <p>{{ $caisse->description ?? 'Aucune description' }}</p>
    </div>
    <div class="page-header__actions">
        @if($caisse->is_active)
            <span class="badge badge--green" style="font-size:13px;padding:6px 14px;">Active</span>
        @else
            <span class="badge badge--gray" style="font-size:13px;padding:6px 14px;">Inactive</span>
        @endif
        <a href="{{ route('pos.caisses.index') }}" class="btn btn--ghost">← Retour</a>
    </div>
</div>

@php
    $totalSessions = $caisse->sessions->count();
    $totalVentes   = $caisse->sessions->sum('total_sales');
    $openSession   = $caisse->sessions->firstWhere('closed_at', null);
@endphp

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
    <div class="card" style="padding:20px;">
        <div style="font-size:11px;color:#94A3B8;text-transform:uppercase;font-weight:700;margin-bottom:6px;">Sessions totales</div>
        <div style="font-size:28px;font-weight:800;">{{ $totalSessions }}</div>
    </div>
    <div class="card" style="padding:20px;">
        <div style="font-size:11px;color:#94A3B8;text-transform:uppercase;font-weight:700;margin-bottom:6px;">Total ventes</div>
        <div style="font-size:28px;font-weight:800;color:#16a34a;">{{ \App\Helpers\FormatHelper::money($totalVentes) }}</div>
    </div>
    <div class="card" style="padding:20px;">
        <div style="font-size:11px;color:#94A3B8;text-transform:uppercase;font-weight:700;margin-bottom:6px;">Statut actuel</div>
        @if($openSession)
            <div style="font-size:16px;font-weight:700;color:#15803d;">Ouverte</div>
            <div style="font-size:12px;color:#64748B;margin-top:4px;">{{ $openSession->user->name }} depuis {{ $openSession->opened_at->format('d/m/Y H:i') }}</div>
        @else
            <div style="font-size:16px;font-weight:700;color:#94A3B8;">Fermée</div>
        @endif
    </div>
</div>

<div class="table-wrapper">
    <div class="table-wrapper__header">
        <strong>Historique des sessions</strong>
        <span style="font-size:13px;color:#64748B;">{{ $totalSessions }} session(s)</span>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Date d'ouverture</th>
                <th>Caissier</th>
                <th>Entrepôt</th>
                <th style="text-align:right">Fond ouverture</th>
                <th style="text-align:center">Ventes</th>
                <th style="text-align:right">Total ventes</th>
                <th style="text-align:center">Statut</th>
                <th style="text-align:right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($caisse->sessions as $session)
            <tr>
                <td style="font-weight:600;">{{ $session->opened_at->format('d/m/Y H:i') }}</td>
                <td>{{ $session->user->name }}</td>
                <td style="font-size:13px;color:#64748B;">{{ $session->warehouse?->name ?? '—' }}</td>
                <td style="text-align:right;">{{ \App\Helpers\FormatHelper::money($session->opening_balance) }}</td>
                <td style="text-align:center;font-size:13px;">{{ $session->sales()->count() }}</td>
                <td style="text-align:right;font-weight:600;color:#16a34a;">{{ \App\Helpers\FormatHelper::money($session->total_sales) }}</td>
                <td style="text-align:center;">
                    @if($session->isOpen())
                        <span class="badge badge--green">Ouverte</span>
                    @else
                        <span class="badge badge--gray">Fermée</span>
                    @endif
                </td>
                <td>
                    <div class="data-table__actions" style="justify-content:flex-end;">
                        <a href="{{ route('pos.sessions.show', $session) }}" class="btn btn--ghost btn--sm">Détail</a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center;padding:40px;color:#64748B;">Aucune session pour cette caisse.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
