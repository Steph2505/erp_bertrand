@extends('layouts.app')
@section('title', 'Rapport de caisse')
@section('breadcrumb')<a href="{{ route('reports.profit-loss') }}">Rapports</a><span class="sep">/</span><span class="current">Caisse POS</span>@endsection

@section('content')
<div x-data="{...listPage('{{ route('reports.api.pos') }}')}" x-init="filters = {date_from: '{{ $from }}', date_to: '{{ $to }}'}; fetch()">
<div class="page-header">
    <div class="page-header__title"><h2>Rapport de caisse (POS)</h2><p>Sessions de caisse par période</p></div>
    <div class="page-header__actions">
        <a href="{{ route('pos.sessions.index') }}" class="btn btn--ghost">Voir sessions</a>
    </div>
</div>

<div class="table-wrapper" style="padding:14px 20px;margin-bottom:16px;">
    <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <div class="form-group" style="margin:0"><label>Du</label><input type="date" x-model="filters.date_from" @change="reset()" class="form-control"></div>
        <div class="form-group" style="margin:0"><label>Au</label><input type="date" x-model="filters.date_to" @change="reset()" class="form-control"></div>
        <button type="button" @click="clearFilters()" class="btn btn--ghost">Réinitialiser</button>
    </div>
</div>

<div class="stat-grid" style="margin-bottom:20px;">
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Sessions</div><div class="stat-card__value" x-text="extra.nb_sessions ?? 0">0</div><div class="stat-card__trend stat-card__trend--flat">Sur la période</div></div><div class="stat-card__icon stat-card__icon--blue"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0H3"/></svg></div></div>
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">CA POS total</div><div class="stat-card__value" style="color:#12864B;" x-text="extra.total_sales_sum ?? '—'">—</div><div class="stat-card__trend stat-card__trend--flat">Toutes caisses</div></div><div class="stat-card__icon stat-card__icon--green"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/></svg></div></div>
    <div class="stat-card"><div class="stat-card__info"><div class="stat-card__label">Moyenne / session</div><div class="stat-card__value" x-text="extra.avg_per_session ?? '—'">—</div><div class="stat-card__trend stat-card__trend--flat">CA moyen</div></div><div class="stat-card__icon stat-card__icon--yellow"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941"/></svg></div></div>
</div>

<div class="table-wrapper">
    <div class="table-wrapper__header"><strong>Sessions de caisse</strong><span style="font-size:13px;color:#64748B;" x-text="total + ' session(s)'">—</span></div>
    <div style="position:relative;">
        <div x-show="loading && rows.length > 0" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
            <svg style="width:28px;height:28px;color:#1749B3;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        </div>
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
                <template x-if="loading && rows.length === 0">
                    <tr><td colspan="9" style="text-align:center;padding:40px;color:#64748B;">Chargement...</td></tr>
                </template>
                <template x-if="!loading && rows.length === 0">
                    <tr><td colspan="9" style="text-align:center;padding:40px;color:#64748B;">Aucune session sur la période</td></tr>
                </template>
                <template x-for="s in rows" :key="s.id">
                    <tr>
                        <td>
                            <a :href="s.show_url" style="color:#1749B3;font-weight:500;" x-text="s.opened_at"></a>
                        </td>
                        <td x-text="s.caisse_name ?? '—'"></td>
                        <td x-text="s.user_name ?? '—'"></td>
                        <td x-text="s.warehouse_name ?? '—'"></td>
                        <td style="text-align:right" x-text="s.opening_balance"></td>
                        <td style="text-align:right;font-weight:600;color:#12864B;" x-text="s.total_sales"></td>
                        <td style="text-align:right" x-text="s.closing_balance ?? '—'"></td>
                        <td style="text-align:right">
                            <template x-if="s.variance !== null">
                                <span :style="'font-weight:600;color:' + (s.variance_sign >= 0 ? '#12864B' : '#C4231A')">
                                    <span x-text="(s.variance_sign >= 0 ? '+' : '') + s.variance"></span>
                                </span>
                            </template>
                            <template x-if="s.variance === null">—</template>
                        </td>
                        <td>
                            <span class="badge" :class="s.is_closed ? 'badge--gray' : 'badge--green'" x-text="s.is_closed ? 'Clôturée' : 'Ouverte'"></span>
                        </td>
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
