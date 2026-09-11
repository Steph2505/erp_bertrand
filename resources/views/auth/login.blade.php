@extends('layouts.auth')
@section('title', 'Connexion')

@section('content')
<div class="auth-wrapper">

    {{-- Panneau gauche : formulaire --}}
    <div class="auth-form-panel">
        <div class="auth-form-panel__logo">
            <div class="auth-form-panel__logo-icon">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}">
            </div>
            <span>Espace Mokolo d'Obala</span>
        </div>

        <div class="auth-form-panel__form">
            <div class="auth-form-panel__header">
                <h1>Bienvenue sur Espace Mokolo d'Obala</h1>
                <p>Connectez-vous à votre espace de gestion</p>
            </div>

            @if(session('status'))
                <div class="alert alert--success mb-16">
                    <div class="alert__content">{{ session('status') }}</div>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" x-data="{ showPassword: false }">
                @csrf

                <div class="form-group">
                    <label for="email">Adresse email <span class="required">*</span></label>
                    <div class="input-group">
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control @error('email') form-control--error @enderror"
                            value="{{ old('email') }}"
                            placeholder="votre@email.com"
                            required
                            autofocus
                        >
                    </div>
                    @error('email')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe <span class="required">*</span></label>
                    <div class="input-group input-group--right">
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            id="password"
                            name="password"
                            class="form-control @error('password') form-control--error @enderror"
                            placeholder="••••••••"
                            required
                        >
                        <button type="button" class="input-icon input-icon--btn" @click="showPassword = !showPassword">
                            <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="icon-eye"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            <svg x-show="showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="icon-eye"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                        </button>
                    </div>
                    @error('password')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="auth-form-panel__remember">
                    <label class="form-checkbox">
                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        Se souvenir de moi
                    </label>
                    <a href="{{ route('password.request') }}" class="auth-link">Mot de passe oublié ?</a>
                </div>

                <button type="submit" class="btn btn--primary w-full" style="justify-content:center;">
                    Se connecter
                </button>
            </form>
        </div>
    </div>

    {{-- Panneau droit : branding --}}
    <div class="auth-brand-panel">
        <div class="auth-brand-panel__circle"></div>
        <div class="auth-brand-panel__content">
            <div class="auth-brand-panel__logo">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}">
            </div>
            <h2 class="auth-brand-panel__title">Espace Mokolo d'Obala</h2>
            <p class="auth-brand-panel__subtitle">Gérez votre centre commercial avec efficacité. Stocks, ventes, packs et rapports en un seul endroit.</p>

            <div class="auth-brand-panel__features">
                <div class="auth-brand-panel__feature">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375"/></svg>
                    <span>Gestion des stocks en temps réel</span>
                </div>
                <div class="auth-brand-panel__feature">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                    <span>Vente à l'unité et en packs</span>
                </div>
                <div class="auth-brand-panel__feature">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
                    <span>Rapports financiers détailléss</span>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
