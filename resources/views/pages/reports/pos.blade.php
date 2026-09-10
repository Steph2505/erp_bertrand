@extends('layouts.app')
@section('title', 'Rapport de caisse')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Caisse POS</span>@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title"><h2>Rapport de caisse (POS)</h2><p>Sessions de caisse par période</p></div>
    <div class="page-header__actions">
        <a href="{{ route('pos.sessions.index') }}" class="btn btn--ghost">Voir sessions</a>
    </div>
</div>

<form method="GET" class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0"><label>Du</label><input type="date" name="date_from" value="{{ $from }}" class="form-control"></div>
        <div class="form-group" style="margin:0"><label>Au</label><input type="date" name="date_to" value="{{ $to }}" class="form-control"></div>
        <button class="btn btn--primary">Filtrer</button>
        <a href="{{ route('reports.pos') }}" class="btn btn--ghost">Réinitialiser</a>
    </div>
</form>

<div class="stat-grid" style="margin-bottom:20px;">
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Sessions</div><div class="stat-card__value">{{ $nbSessions }}</div><div class="stat-card__trend stat-card__trend--flat">Sur la période</div></div><div class="stat-card__icon stat-card__icon--blue"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0H3"/></svg></div></div>
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">CA POS total</div><div class="stat-card__value" style="color:#12864B;">{{ \App\Helpers\FormatHelper::money($totalSales) }}</div><div class="stat-card__trend stat-card__trend--flat">Toutes caisses</div></div><div class="stat-card__icon stat-card__icon--green"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/></svg></div></div>
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Moyenne / session</div><div class="stat-card__value">{{ \App\Helpers\FormatHelper::money($avgPerSession) }}</div><div class="stat-card__trend stat-card__trend--flat">CA moyen</div></div><div class="stat-card__icon stat-card__icon--yellow"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/></svg></div></div>
</div>

<div class="table-wrapper">
    <div class="table-wrapper__header"><strong>Sessions de caisse</strong><span style="font-size:13px;color:#64748B;">{{ $sessions->total() }} session(s)</span></div>
    <table class="data-table">
        <thead><tr>
            <th>Date ouverture</th><th>Caisse</th><th>Vendeur</th><th>Entrepôt</th>
            <th style="text-align:right">Fond départ</th>
            <th style="text-align:right">CA session</th>
            <th style="text-align:right">Fond clôture</th>
            <th style="text-align:right">Écart</th>
            <th>Statut</th>
        </tr></thead>
        <tbody>
            @forelse($sessions as $s)
            @php
                $expected = (float)$s->opening_balance + (float)$s->total_sales;
                $variance = $s->closed_at ? ((float)$s->closing_balance - $expected) : null;
            @endphp
            <tr>
                <td>
                    <a href="{{ route('pos.sessions.show', $s->id) }}" style="color:#1749B3;font-weight:500;">
                        {{ \App\Helpers\FormatHelper::datetime($s->opened_at) }}
                    </a>
                </td>
                <td>{{ $s->caisse?->name ?? '—' }}</td>
                <td>{{ $s->user?->name ?? '—' }}</td>
                <td>{{ $s->warehouse?->name ?? '—' }}</td>
                <td style="text-align:right">{{ \App\Helpers\FormatHelper::money((float) $s->opening_balance) }}</td>
                <td style="text-align:right;font-weight:600;color:#12864B;">{{ \App\Helpers\FormatHelper::money((float) $s->total_sales) }}</td>
                <td style="text-align:right">{{ $s->closed_at ? \App\Helpers\FormatHelper::money((float) $s->closing_balance) : '—' }}</td>
                <td style="text-align:right">
                    @if($variance !== null)
                        @php $varColor = abs($variance) < 1 ? '#12864B' : ($variance > 0 ? '#12864B' : '#C4231A'); @endphp
                        <span style="color:{{ $varColor }};font-weight:600;">
                            {{ $variance >= 0 ? '+' : '' }}{{ \App\Helpers\FormatHelper::money((float) $variance) }}
                        </span>
                    @else —
                    @endif
                </td>
                <td>
                    @if($s->closed_at) <span class="badge badge--gray">Clôturée</span>
                    @else <span class="badge badge--green">Ouverte</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="9" style="text-align:center;padding:40px;color:#64748B;">Aucune session sur la période</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="table-wrapper__footer">
        <span>{{ $sessions->firstItem()??0 }}–{{ $sessions->lastItem()??0 }} sur {{ $sessions->total() }}</span>
        {{ $sessions->withQueryString()->links() }}
    </div>
</div>
@endsection
