@extends('layouts.app')
@section('title', "Journal d'activité")
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Activité</span>@endsection

@section('content')
<div x-data="{...listPage('{{ route('reports.api.activity') }}')}" x-init="filters = {date_from: '{{ $from }}', date_to: '{{ $to }}'}; fetch()">
<div class="page-header">
    <div class="page-header__title"><h2>Journal d'activité</h2><p>Traçabilité des actions utilisateurs</p></div>
</div>

<div class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0"><label>Du</label><input type="date" x-model="filters.date_from" @change="reset()" class="form-control"></div>
        <div class="form-group" style="margin:0"><label>Au</label><input type="date" x-model="filters.date_to" @change="reset()" class="form-control"></div>
        <button type="button" @click="clearFilters()" class="btn btn--ghost">Réinitialiser</button>
    </div>
</div>

<div class="table-wrapper">
    <div class="table-wrapper__header">
        <strong>Journal</strong>
        <span style="font-size:13px;color:#64748B;" x-text="total + ' entrée(s)'">—</span>
    </div>
    <div style="position:relative;">
        <div x-show="loading && rows.length > 0" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
            <svg style="width:28px;height:28px;color:#1749B3;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>Date & heure</th><th>Utilisateur</th><th>Action</th><th>Description</th><th>IP</th>
            </tr></thead>
            <tbody>
                <template x-if="loading && rows.length === 0">
                    <tr><td colspan="5" style="text-align:center;padding:60px;color:#64748B;">Chargement...</td></tr>
                </template>
                <template x-if="!loading && rows.length === 0">
                    <tr>
                        <td colspan="5" style="text-align:center;padding:60px;color:#64748B;">
                            <div style="margin-bottom:8px;font-size:15px;font-weight:600;">Aucune activité enregistrée</div>
                            <div style="font-size:13px;">Les actions sont tracées via <code>ActivityLog::log()</code>.</div>
                        </td>
                    </tr>
                </template>
                <template x-for="log in rows" :key="log.created_at + log.action + log.description">
                    <tr>
                        <td style="font-size:12px;color:#64748B;white-space:nowrap;" x-text="log.created_at"></td>
                        <td x-text="log.user_name ?? '—'"></td>
                        <td>
                            <span class="badge" :class="log.action.includes('create') ? 'badge--green' : (log.action.includes('delete') ? 'badge--red' : (log.action.includes('update') ? 'badge--blue' : 'badge--gray'))" x-text="log.action"></span>
                        </td>
                        <td style="max-width:300px;font-size:13px;" x-text="log.description"></td>
                        <td style="font-size:11px;color:#94A3B8;" x-text="log.ip_address"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
    <div class="table-wrapper__footer">
        <span x-text="from + '–' + to + ' sur ' + total"></span>
        <div style="display:flex;gap:4px;" x-show="lastPage > 1">
            <button @click="goTo(currentPage-1)" :disabled="currentPage<=1||loading" class="btn btn--ghost btn--sm btn--icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg></button>
            <template x-for="p in pages" :key="p"><button @click="p!=='…'&&goTo(p)" class="btn btn--sm" :class="p===currentPage?'btn--primary':'btn--ghost'" :disabled="p==='…'||loading" x-text="p" style="min-width:34px;justify-content:center;"></button></template>
            <button @click="goTo(currentPage+1)" :disabled="currentPage>=lastPage||loading" class="btn btn--ghost btn--sm btn--icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg></button>
        </div>
    </div>
</div>
</div>
@include('components.list-page-script')
@endsection
