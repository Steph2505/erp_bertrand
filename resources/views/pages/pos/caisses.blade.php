@extends('layouts.app')
@section('title', 'Liste des caisses')
@section('breadcrumb')
    <a href="{{ route('pos.index') }}">POS</a>
    <span class="sep">/</span><span class="current">Liste des caisses</span>
@endsection

@section('content')
<div x-data="caisseSearch()" x-init="init()">

{{-- ── En-tête ──────────────────────────────────────────────────────────── --}}
<div class="page-header">
    <div class="page-header__title">
        <h2>Liste des caisses</h2>
        <p x-text="query ? total + ' résultat(s) pour « ' + query + ' »' : '{{ $caisses->count() }} caisse(s)'"></p>
    </div>
    <div class="page-header__actions">
        @if($openSessions->count() > 0)
        <button @click="showSessions = !showSessions" class="btn btn--ghost">
            <span class="badge badge--green" style="font-size:11px;padding:2px 8px;">{{ $openSessions->count() }}</span>
            Sessions actives
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px;" :style="showSessions ? 'transform:rotate(180deg)' : ''"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
        </button>
        @endif
        <button @click="showModal = true" class="btn btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nouvelle caisse
        </button>
    </div>
</div>

{{-- ── Alertes ──────────────────────────────────────────────────────────── --}}
@if(session('success'))
<div class="alert alert--success mb-16"><div class="alert__content">{{ session('success') }}</div></div>
@endif
@if($errors->has('caisse'))
<div class="alert alert--danger mb-16"><div class="alert__content">{{ $errors->first('caisse') }}</div></div>
@endif

{{-- ── Sessions ouvertes ─────────────────────────────────────────────────── --}}
@if($openSessions->count() > 0)
<div x-show="showSessions" x-collapse class="card mb-24" style="border-color:rgba(34,197,94,0.4);background:rgba(34,197,94,0.03);">
    <div style="padding:14px 20px;border-bottom:1px solid rgba(34,197,94,0.2);display:flex;align-items:center;gap:10px;">
        <span style="width:10px;height:10px;border-radius:50%;background:#12864B;display:inline-block;animation:caisse-pulse 1.8s ease-in-out infinite;flex-shrink:0;"></span>
        <strong style="font-size:14px;color:#15803d;">{{ $openSessions->count() }} session(s) ouverte(s) en ce moment</strong>
    </div>
    @foreach($openSessions as $session)
    <div style="display:flex;align-items:center;gap:16px;padding:12px 20px;border-bottom:1px solid rgba(34,197,94,0.1);">
        <div style="flex:1;min-width:0;">
            <div style="font-weight:600;font-size:14px;">{{ $session->caisse?->name ?? '—' }}</div>
            <div style="font-size:12px;color:#64748B;">
                {{ $session->user->name }}
                @if($session->warehouse) · {{ $session->warehouse->name }} @endif
            </div>
        </div>
        <div style="text-align:right;flex-shrink:0;">
            <div style="font-size:13px;color:#15803d;font-weight:600;">Ouverte à {{ $session->opened_at->format('H:i') }}</div>
            <div style="font-size:12px;color:#64748B;">{{ $session->opened_at->diffForHumans() }}</div>
        </div>
        <div style="display:flex;gap:8px;flex-shrink:0;">
            <a href="{{ route('pos.sessions.show', $session) }}" class="btn btn--ghost btn--sm">Détail</a>
            @if($session->user_id === auth()->id())
            <a href="{{ route('pos.index') }}" class="btn btn--primary btn--sm">Continuer</a>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- ── Recherche AJAX ───────────────────────────────────────────────────── --}}
<div class="mb-24">
    <div class="filters-bar">
        <div class="input-group" style="flex:1;max-width:420px;">
            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
            <input type="text"
                   x-model="query"
                   @input.debounce.350ms="fetch()"
                   @keydown.escape="clear()"
                   class="form-control"
                   placeholder="Rechercher une caisse...">
        </div>
        <button x-show="query" @click="clear()" class="btn btn--ghost">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            Effacer
        </button>
        <div x-show="loading" class="text-muted text-sm" style="display:flex;align-items:center;gap:6px;">
            <svg class="animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="width:16px;height:16px;animation:spin 1s linear infinite;"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.416" stroke-dashoffset="10" opacity=".3"/><path stroke="currentColor" stroke-width="3" stroke-linecap="round" d="M12 2a10 10 0 0 1 10 10"/></svg>
            Recherche...
        </div>
    </div>
</div>

{{-- ── Grille ───────────────────────────────────────────────────────────── --}}

{{-- État vide --}}
<template x-if="!loading && caisses.length === 0">
    <div class="card" style="padding:48px;text-align:center;color:#64748B;">
        <div style="font-size:48px;margin-bottom:16px;">🔍</div>
        <p style="font-size:15px;font-weight:600;margin-bottom:8px;" x-text="query ? 'Aucune caisse pour « ' + query + ' »' : 'Aucune caisse configurée'"></p>
        <template x-if="query">
            <button @click="clear()" class="btn btn--ghost">Voir toutes les caisses</button>
        </template>
        <template x-if="!query">
            <button @click="showModal = true" class="btn btn--primary">Créer une caisse</button>
        </template>
    </div>
</template>

{{-- Cards --}}
<div class="caisse-grid" x-show="caisses.length > 0">
    <template x-for="caisse in caisses" :key="caisse.id">
        <div class="caisse-card" :class="caisse.is_active ? '' : 'caisse-card--inactive'"
             x-data="{ editOpen: false }">

            {{-- En-tête --}}
            <div class="caisse-card__header">
                <div class="caisse-card__icon">🏧</div>
                <div class="caisse-card__meta">
                    <div class="caisse-card__name" x-text="caisse.name"></div>
                    <div class="caisse-card__desc" x-text="caisse.description || 'Pas de description'" x-show="caisse.description || !caisse.is_active"></div>
                </div>
                <span class="badge" :class="caisse.is_active ? 'badge--green' : 'badge--gray'"
                      x-text="caisse.is_active ? 'Active' : 'Inactive'"></span>
            </div>

            {{-- Corps --}}
            <div class="caisse-card__body">
                <template x-if="caisse.open_session">
                    <div class="caisse-card__session-live">
                        <div class="caisse-card__session-live-dot"></div>
                        <div class="caisse-card__session-live-info">
                            <div class="caisse-card__session-live-name" x-text="caisse.open_session.user_name"></div>
                            <div class="caisse-card__session-live-time" x-text="'ouverte à ' + caisse.open_session.opened_at + ' · ' + caisse.open_session.diff"></div>
                        </div>
                    </div>
                </template>
                <template x-if="!caisse.open_session">
                    <div class="caisse-card__no-session">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        Aucune session ouverte
                    </div>
                </template>

                <div class="caisse-card__stat">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605"/></svg>
                    <strong x-text="caisse.sessions_count"></strong>
                    <span>session(s) au total</span>
                </div>
            </div>

            {{-- Pied --}}
            <div class="caisse-card__footer">
                <template x-if="caisse.my_session">
                    <a href="{{ route('pos.index') }}" class="btn btn--primary btn--sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z"/></svg>
                        Continuer
                    </a>
                </template>
                <template x-if="!caisse.my_session && caisse.is_active">
                    <button @click="startSession(caisse)" class="btn btn--primary btn--sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        Ouvrir
                    </button>
                </template>
                <template x-if="!caisse.my_session && !caisse.is_active">
                    <span class="btn btn--ghost btn--sm" style="opacity:.4;cursor:default;">Inactive</span>
                </template>

                <div class="caisse-card__footer-spacer"></div>

                <template x-if="caisse.sessions_count > 0">
                    <a :href="caisse.show_url" class="btn btn--ghost btn--sm btn--icon" title="Voir les sessions">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                    </a>
                </template>

                <button @click="editOpen = !editOpen" class="btn btn--ghost btn--sm btn--icon" title="Modifier">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                </button>

                <form :action="caisse.toggle_url" method="POST">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn--ghost btn--sm btn--icon"
                            :class="caisse.is_active ? 'text-danger' : 'text-success'"
                            :title="caisse.is_active ? 'Désactiver' : 'Activer'">
                        <template x-if="caisse.is_active">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        </template>
                        <template x-if="!caisse.is_active">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        </template>
                    </button>
                </form>

                <template x-if="caisse.sessions_count === 0">
                    <form :action="caisse.destroy_url" method="POST"
                          @submit.prevent="window.confirmDialog('Supprimer définitivement cette caisse ?', {variant:'danger', confirmLabel:'Supprimer'}).then(ok => ok && $el.submit())">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn--ghost btn--sm btn--icon btn-table-delete" title="Supprimer">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                        </button>
                    </form>
                </template>
                <template x-if="caisse.sessions_count > 0">
                    <span class="btn btn--ghost btn--sm btn--icon" style="opacity:.3;cursor:not-allowed;"
                          title="Suppression impossible : historique existant. Désactivez la caisse.">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                    </span>
                </template>
            </div>

            {{-- Formulaire édition inline --}}
            <div x-show="editOpen" x-collapse class="caisse-card__edit-form">
                <form :action="caisse.update_url" method="POST"
                      style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;width:100%;">
                    @csrf @method('PUT')
                    <div class="form-group" style="flex:1;min-width:130px;margin-bottom:0;">
                        <label style="font-size:12px;">Nom</label>
                        <input type="text" name="name" :value="caisse.name" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex:2;min-width:180px;margin-bottom:0;">
                        <label style="font-size:12px;">Description</label>
                        <input type="text" name="description" :value="caisse.description" class="form-control" placeholder="Optionnel">
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button type="submit" class="btn btn--primary btn--sm">Sauver</button>
                        <button type="button" @click="editOpen = false" class="btn btn--ghost btn--sm">Annuler</button>
                    </div>
                </form>
            </div>

        </div>
    </template>
</div>

{{-- ── Modal nouvelle caisse ────────────────────────────────────────────── --}}
<div class="modal-overlay" x-show="showModal" x-cloak x-transition @click.self="showModal = false" @keydown.escape.window="showModal = false">
    <div class="modal modal--sm">
        <div class="modal__header">
            <h3>Nouvelle caisse</h3>
            <button @click="showModal = false" class="modal__close">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('pos.caisses.store') }}" class="modal__body">
            @csrf
            <div class="form-group">
                <label>Nom <span class="required">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="Ex : Caisse principale" autofocus required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <input type="text" name="description" class="form-control" placeholder="Optionnel">
            </div>
            @if($errors->any() && !$errors->has('caisse'))
            <div class="alert alert--danger" style="margin-bottom:12px;">
                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
            @endif
            <div class="modal-footer-std">
                <button type="button" @click="showModal = false" class="btn btn--ghost">Annuler</button>
                <button type="submit" class="btn btn--primary">Créer la caisse</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal ouvrir une session ─────────────────────────────────────────── --}}
<div class="modal-overlay" x-show="openModal" x-cloak x-transition @click.self="openModal = false">
    <div class="modal modal--sm">
        <div class="modal__header">
            <h3>Ouvrir la caisse</h3>
            <button @click="openModal = false" class="modal__close">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('pos.sessions.open') }}" class="modal__body">
            @csrf
            <input type="hidden" name="caisse_id" :value="openCaisse?.id">
            <div class="form-group">
                <label>Caisse sélectionnée</label>
                <div class="form-control" style="background:#f8fafc;cursor:default;font-weight:600;" x-text="openCaisse?.name"></div>
            </div>
            <div class="form-group">
                <label>Fond d'ouverture ({{ $currency }}) <span class="required">*</span></label>
                <input type="number" name="opening_balance" step="1" min="0" value="0"
                       class="form-control input--payment-amount" required>
            </div>
            @if(count($warehouses))
            <div class="form-group">
                <label>Entrepôt (optionnel)</label>
                <select name="warehouse_id" class="form-select">
                    <option value="">— Sélectionner —</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="modal-footer-std">
                <button type="button" @click="openModal = false" class="btn btn--ghost">Annuler</button>
                <button type="submit" class="btn btn--primary">Ouvrir et commencer</button>
            </div>
        </form>
    </div>
</div>

</div>{{-- /x-data --}}

@php
$currentUserId = auth()->id();
$caissesJson = $caisses->map(function ($c) use ($currentUserId) {
    $openSession = $c->sessions->first();
    return [
        'id'             => $c->id,
        'name'           => $c->name,
        'description'    => $c->description,
        'is_active'      => $c->is_active,
        'sessions_count' => $c->sessions_count,
        'my_session'     => (bool) $c->sessions->firstWhere('user_id', $currentUserId),
        'open_session'   => $openSession ? [
            'user_name' => $openSession->user->name,
            'opened_at' => $openSession->opened_at->format('H:i'),
            'diff'      => $openSession->opened_at->diffForHumans(),
        ] : null,
        'show_url'    => route('pos.caisses.show', $c->id),
        'toggle_url'  => route('pos.caisses.toggle', $c->id),
        'destroy_url' => route('pos.caisses.destroy', $c->id),
        'update_url'  => route('pos.caisses.update', $c->id),
    ];
});
@endphp

@push('scripts')
<script>
function caisseSearch() {
    return {
        query:        '',
        loading:      false,
        showModal:    false,
        openModal:    false,
        openCaisse:   null,
        showSessions: {{ $openSessions->count() > 0 ? 'true' : 'false' }},
        caisses:      @json($caissesJson),

        get total() { return this.caisses.length; },

        init() { /* liste déjà chargée */ },

        async fetch() {
            this.loading = true;
            try {
                const res  = await window.fetch(`{{ route('pos.caisses.search') }}?q=${encodeURIComponent(this.query)}`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                });
                const data = await res.json();
                this.caisses = data.caisses;
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        },

        clear() {
            this.query = '';
            this.fetch();
        },

        startSession(caisse) {
            this.openCaisse = caisse;
            this.openModal  = true;
        },
    };
}
</script>
@endpush
@endsection
