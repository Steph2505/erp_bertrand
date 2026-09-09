@extends('layouts.app')
@section('title', 'Accès interdit')

@section('content')
<div class="error-page">

    <div class="error-page__icon-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="error-page__icon">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
        </svg>
    </div>

    <div class="error-page__body">
        <h1 class="error-page__title">Accès interdit</h1>
        <p class="error-page__desc">
            Vous n'avez pas les droits nécessaires pour accéder à cette page.<br>
            Contactez votre administrateur si vous pensez que c'est une erreur.
        </p>
    </div>

    <div class="error-page__actions">
        <a href="{{ route('dashboard') }}" class="btn btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="error-page__btn-icon"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
            Tableau de bord
        </a>
        <a href="javascript:history.back()" class="btn btn--ghost">Retour</a>
    </div>

    <p class="error-page__info">
        Connecté en tant que : <strong>{{ auth()->user()?->name }}</strong>
        — Rôle : <strong>{{ auth()->user()?->roles->first()?->name ?? '—' }}</strong>
    </p>
</div>
@endsection
