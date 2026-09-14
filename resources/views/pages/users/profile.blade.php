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

<div class="user-profile__layout">

    <div class="card user-profile__card">
        <h3 class="user-profile__card-title">Informations personnelles</h3>
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
                <input type="text" class="form-control user-profile__role-input" value="{{ $user->getRoleNames()->first() ?? 'Aucun rôle' }}" disabled>
            </div>
            <button type="submit" class="btn btn--primary">Mettre à jour</button>
        </form>
    </div>

    <div class="card user-profile__card">
        <h3 class="user-profile__card-title">Changer le mot de passe</h3>
        <form method="POST" action="{{ route('users.profile.update') }}" x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
            @csrf @method('PUT')
            <input type="hidden" name="name" value="{{ $user->name }}">
            <input type="hidden" name="email" value="{{ $user->email }}">
            <div class="form-group">
                <label>Mot de passe actuel <span class="required">*</span></label>
                <div class="input-group input-group--right">
                    <input :type="showCurrent ? 'text' : 'password'" name="current_password" class="form-control @error('current_password') form-control--error @enderror" required autocomplete="current-password">
                    <button type="button" class="input-icon input-icon--btn" @click="showCurrent = !showCurrent">
                        <svg x-show="!showCurrent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="icon-eye"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                        <svg x-show="showCurrent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="icon-eye"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                    </button>
                </div>
                @error('current_password') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>Nouveau mot de passe</label>
                <div class="input-group input-group--right">
                    <input :type="showNew ? 'text' : 'password'" name="password" class="form-control" minlength="8" placeholder="8 caractères minimum">
                    <button type="button" class="input-icon input-icon--btn" @click="showNew = !showNew">
                        <svg x-show="!showNew" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="icon-eye"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                        <svg x-show="showNew" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="icon-eye"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label>Confirmer le mot de passe</label>
                <div class="input-group input-group--right">
                    <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation" class="form-control">
                    <button type="button" class="input-icon input-icon--btn" @click="showConfirm = !showConfirm">
                        <svg x-show="!showConfirm" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="icon-eye"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                        <svg x-show="showConfirm" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="icon-eye"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn--warning">Changer le mot de passe</button>
        </form>

        <hr class="user-profile__divider">

        <div class="user-profile__danger-zone">
            <h4 class="user-profile__danger-title">Zone de danger</h4>
            <p class="user-profile__danger-text">
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
