@extends('layouts.app')
@section('title', $user->name)
@section('breadcrumb')
    <a href="{{ route('users.index') }}">Utilisateurs</a>
    <span class="sep">/</span><span class="current">{{ $user->name }}</span>
@endsection

@section('content')

<div class="page-header">
    <div class="page-header__title">
        <h2>{{ $user->name }}</h2>
        <p style="color:#64748b;">{{ $user->email }}</p>
    </div>
    <div class="page-header__actions">
        <a href="{{ route('users.index') }}" class="btn btn--ghost">← Retour</a>
    </div>
</div>

<div class="user-profile__layout">

    <div class="card user-profile__card">
        <h3 class="user-profile__card-title">Informations</h3>
        <div class="form-group">
            <label>Nom complet</label>
            <div class="form-control" style="background:#f8fafc;color:#334155;">{{ $user->name }}</div>
        </div>
        <div class="form-group">
            <label>Email</label>
            <div class="form-control" style="background:#f8fafc;color:#334155;">{{ $user->email }}</div>
        </div>
        <div class="form-group">
            <label>Téléphone</label>
            <div class="form-control" style="background:#f8fafc;color:#334155;">{{ $user->phone ?? '—' }}</div>
        </div>
        <div class="form-group">
            <label>Rôle</label>
            <div>
                @forelse($user->roles as $role)
                    <span class="badge badge--blue">{{ $role->name }}</span>
                @empty
                    <span style="font-size:13px;color:#94A3B8;">Aucun rôle</span>
                @endforelse
            </div>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label>Statut</label>
            <div>
                <span class="badge badge--{{ $user->is_active ? 'green' : 'gray' }}">{{ $user->is_active ? 'Actif' : 'Inactif' }}</span>
            </div>
        </div>
    </div>

    <div class="card user-profile__card">
        <h3 class="user-profile__card-title">Droits d'accès</h3>

        @php $userPerms = $user->getAllPermissions()->pluck('name'); @endphp

        @foreach($permissionGroups as $group => $perms)
            <div style="margin-bottom:16px;">
                <div style="font-size:11px;font-weight:700;color:#64748B;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">
                    {{ $group }}
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    @foreach($perms as $perm)
                        @php $has = $userPerms->contains($perm); @endphp
                        <span style="display:flex;align-items:center;gap:6px;padding:6px 12px;border:1.5px solid {{ $has ? '#bbf7d0' : '#e2e8f0' }};background:{{ $has ? '#f0fdf4' : '#f8fafc' }};color:{{ $has ? '#15803d' : '#94A3B8' }};border-radius:8px;font-size:13px;">
                            @if($has)
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            @endif
                            {{ $permissionLabels[$perm] ?? ucfirst($perm) }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endforeach

        @if($userPerms->isEmpty())
            <p style="font-size:13px;color:#94A3B8;">Cet utilisateur n'a aucun droit d'accès.</p>
        @endif
    </div>

</div>
@endsection
