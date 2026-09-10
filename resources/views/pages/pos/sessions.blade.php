@extends('layouts.app')
@section('title', 'Caisses — Historique')
@section('breadcrumb')<a href="{{ route('pos.index') }}">POS</a> <span class="current">Sessions caisse</span>@endsection

@section('content')
<div x-data="listPage('{{ route('pos.sessions.api.list') }}')" x-init="fetch()">

<div class="page-header">
    <div class="page-header__title">
        <h2>Sessions caisse</h2>
        <p x-text="total + ' session(s)'">—</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('pos.index') }}" class="btn btn--primary">Ouvrir le POS</a>
    </div>
</div>

<div class="table-wrapper">
    <div style="position:relative;">
        <div x-show="loading && rows.length > 0" style="position:absolute;inset:0;background:rgba(255,255,255,.6);z-index:5;display:flex;align-items:center;justify-content:center;">
            <svg style="width:28px;height:28px;color:#1749B3;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".25"/><path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>Ref / Caissier</th><th>Entrepôt</th><th>Ouverture</th>
                <th>Fond ouv.</th><th>Total ventes</th><th>Fermeture</th>
                <th>Statut</th><th class="th-right">Actions</th>
            </tr></thead>
            <tbody>
                <template x-if="loading && rows.length === 0">
                    <tr><td colspan="8" style="text-align:center;padding:40px;color:#64748B;">Chargement...</td></tr>
                </template>
                <template x-if="!loading && rows.length === 0">
                    <tr><td colspan="8" class="sale-index__empty-row"><td>Aucune session</td></tr>
                </template>
                <template x-for="s in rows" :key="s.id">
                    <tr>
                        <td>
                            <div class="font-600" x-text="s.caissier"></div>
                            <div class="text-sm text-muted" x-text="s.date"></div>
                        </td>
                        <td class="text-muted text-md" x-text="s.warehouse"></td>
                        <td class="text-md" x-text="s.opened_at"></td>
                        <td><strong x-text="s.opening_balance"></strong></td>
                        <td class="font-600" style="color:#16a34a;" x-text="s.total_sales"></td>
                        <td class="text-md">
                            <span x-text="s.closed_at ?? '—'"></span>
                            <div class="text-xs text-muted" x-show="s.closing_balance" x-text="s.closing_balance"></div>
                        </td>
                        <td>
                            <span class="badge" :class="s.is_open ? 'badge--green' : 'badge--gray'"
                                  x-text="s.is_open ? 'Ouverte' : 'Fermée'"></span>
                        </td>
                        <td>
                            <div class="data-table__actions">
                                <a :href="s.show_url" class="btn btn--ghost btn--sm">Détail</a>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
    <div class="table-wrapper__footer">
        <span x-text="from + '–' + to + ' sur ' + total" class="text-muted text-sm"></span>
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
