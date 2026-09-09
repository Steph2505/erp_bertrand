@extends('layouts.auth')
@section('title', 'Mot de passe oublié')

@section('content')
<div class="auth-standalone">
    <div class="auth-standalone__card">
        <div class="auth-standalone__header">
            <div class="auth-standalone__icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
            </div>
            <h2 class="auth-standalone__title">Mot de passe oublié ?</h2>
            <p class="auth-standalone__subtitle">Entrez votre adresse email et nous vous enverrons un lien de réinitialisation.</p>
        </div>

        @if(session('status'))
            <div class="alert alert--success">
                <div class="alert__content">{{ session('status') }}</div>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="form-group">
                <label for="email">Adresse email <span class="required">*</span></label>
                <div class="input-group">
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
                    <input type="email" id="email" name="email" class="form-control @error('email') form-control--error @enderror" value="{{ old('email') }}" placeholder="votre@email.com" required autofocus>
                </div>
                @error('email')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>
            <button type="submit" class="btn btn--primary btn--block w-full">Envoyer le lien</button>
        </form>

        <div class="auth-standalone__footer">
            <a href="{{ route('login') }}" class="auth-link">← Retour à la connexion</a>
        </div>
    </div>
</div>
@endsection
