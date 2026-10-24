@extends('layouts.app')
@section('title', 'Vérification de caisse')
@section('breadcrumb')<span class="current">Vérification de caisse</span>@endsection

@section('content')
<div style="max-width:460px;margin:64px auto;">
    <div class="card" style="padding:28px;">
        <h2 style="font-size:20px;margin-bottom:8px;">Confirmer votre accès au POS</h2>
        <p style="color:#64748B;margin-bottom:20px;">
            La session de la caisse <strong>{{ $activeSession->caisse?->name ?? 'POS' }}</strong> est déjà ouverte.
            @if(auth()->user()->pos_pin_hash)
                Saisissez votre PIN personnel pour accéder à la session.
            @else
                Créez votre PIN personnel pour sécuriser l’accès à cette session.
            @endif
        </p>
        <form method="POST"
              action="{{ route('pos.sessions.verify-access', $activeSession) }}"
              data-verify-url="{{ route('pos.sessions.verify-access', $activeSession) }}"
              x-data
              @submit.prevent="window.verifyPosSessionAccess($el)">
            @csrf
            <div class="form-group">
                <label for="pin">{{ auth()->user()->pos_pin_hash ? 'Votre PIN' : 'Créez votre PIN à 6 chiffres' }}</label>
                <input id="pin" name="pin" type="password"
                       inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                       autocomplete="off" class="form-control" placeholder="PIN à 6 chiffres" required autofocus>
            </div>
            @unless(auth()->user()->pos_pin_hash)
                <div class="form-group">
                    <label for="pin_confirmation">Confirmez votre PIN</label>
                    <input id="pin_confirmation" name="pin_confirmation" type="password" inputmode="numeric"
                           pattern="[0-9]{6}" maxlength="6" autocomplete="off" class="form-control"
                           placeholder="Confirmez le PIN à 6 chiffres" required>
                </div>
            @endunless
            <button type="submit" class="btn btn--primary" style="width:100%;justify-content:center;">
                Vérifier et accéder au POS
            </button>
        </form>
    </div>
</div>
@endsection