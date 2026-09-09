@extends('layouts.app')
@section('title', 'Mon profil')
@section('breadcrumb')<span class="current">Profil</span>@endsection

@section('content')
<div class="page-header">
    <div class="page-header__title">
        <h2>Mon profil</h2>
        <p>Gérer vos informations personnelles</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

    <div class="card" style="padding:24px;">
        <h3 style="font-size:15px;font-weight:600;margin-bottom:20px;">Informations personnelles</h3>
        <form method="POST" action="{{ route('users.profile.update') }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label>Nom complet <span class="required">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
            </div>
            <div class="form-group">
                <label>Email <span class="required">*</span></label>
                <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
            </div>
            <div class="form-group">
                <label>Téléphone</label>
                <input type="text" name="phone" class="form-control" value="{{ $user->phone ?? '' }}">
            </div>
            <div class="form-group">
                <label>Rôle</label>
                <input type="text" class="form-control" value="{{ $user->getRoleNames()->first() ?? 'Aucun rôle' }}" disabled style="background:#f8fafc;color:#94A3B8;">
            </div>
            <button type="submit" class="btn btn--primary">Mettre à jour</button>
        </form>
    </div>

    <div class="card" style="padding:24px;">
        <h3 style="font-size:15px;font-weight:600;margin-bottom:20px;">Changer le mot de passe</h3>
        <form method="POST" action="{{ route('users.profile.update') }}">
            @csrf @method('PUT')
            <input type="hidden" name="name" value="{{ $user->name }}">
            <input type="hidden" name="email" value="{{ $user->email }}">
            <div class="form-group">
                <label>Nouveau mot de passe</label>
                <input type="password" name="password" class="form-control" minlength="8" placeholder="8 caractères minimum">
            </div>
            <div class="form-group">
                <label>Confirmer le mot de passe</label>
                <input type="password" name="password_confirmation" class="form-control">
            </div>
            <button type="submit" class="btn btn--warning">Changer le mot de passe</button>
        </form>

        <hr style="margin:24px 0;border:none;border-top:1px solid #f1f5f9;">

        <div style="background:#fef2f2;border-radius:8px;padding:16px;">
            <h4 style="font-size:13px;font-weight:600;color:#ef4444;margin-bottom:8px;">Zone de danger</h4>
            <p style="font-size:13px;color:#64748B;margin-bottom:12px;">
                La déconnexion mettra fin à votre session sur tous les appareils.
            </p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn--danger btn--sm">Se déconnecter</button>
            </form>
        </div>
    </div>

</div>
@endsection
