@extends('layouts.app')
@section('title', "Journal d'activité")
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Activité</span>@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title"><h2>Journal d'activité</h2><p>Traçabilité des actions utilisateurs</p></div>
</div>

<form method="GET" class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0"><label>Du</label><input type="date" name="date_from" value="{{ $from }}" class="form-control"></div>
        <div class="form-group" style="margin:0"><label>Au</label><input type="date" name="date_to" value="{{ $to }}" class="form-control"></div>
        <button class="btn btn--primary">Filtrer</button>
        <a href="{{ route('reports.activity') }}" class="btn btn--ghost">Réinitialiser</a>
    </div>
</form>

<div class="table-wrapper">
    <div class="table-wrapper__header">
        <strong>Journal</strong>
        <span style="font-size:13px;color:#64748B;">{{ $logs->total() }} entrée(s)</span>
    </div>
    <table class="data-table">
        <thead><tr>
            <th>Date & heure</th><th>Utilisateur</th><th>Action</th><th>Description</th><th>IP</th>
        </tr></thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td style="font-size:12px;color:#64748B;white-space:nowrap;">{{ \App\Helpers\FormatHelper::datetime($log->created_at) }}</td>
                <td>{{ $log->user?->name ?? '—' }}</td>
                <td>
                    <span class="badge badge--{{
                        str_contains($log->action,'create') ? 'green' :
                        (str_contains($log->action,'delete') ? 'red' :
                        (str_contains($log->action,'update') ? 'blue' : 'gray'))
                    }}">{{ $log->action }}</span>
                </td>
                <td style="max-width:300px;font-size:13px;">{{ $log->description }}</td>
                <td style="font-size:11px;color:#94A3B8;">{{ $log->ip_address }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center;padding:60px;color:#64748B;">
                    <div style="margin-bottom:8px;font-size:15px;font-weight:600;">Aucune activité enregistrée</div>
                    <div style="font-size:13px;">Les actions sont tracées via <code>ActivityLog::log()</code>.</div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="table-wrapper__footer">
        <span>{{ $logs->firstItem()??0 }}–{{ $logs->lastItem()??0 }} sur {{ $logs->total() }}</span>
        {{ $logs->withQueryString()->links() }}
    </div>
</div>
@endsection
