@extends('layouts.app')
@section('title', 'Vérification de caisse')
@section('breadcrumb')<span class="current">Vérification de caisse</span>@endsection

@section('content')
<div style="max-width:460px;margin:64px auto;">
    <div class="card" style="padding:28px;">
        <h2 style="font-size:20px;margin-bottom:8px;">Confirmer votre accès au POS</h2>
        <p style="color:#64748B;margin-bottom:20px;">
            La session de la caisse <strong>{{ $activeSession->caisse?->name ?? 'POS' }}</strong> est déjà ouverte.
            Saisissez le code reçu lors de l’ouverture de cette session. Aucun nouveau code ne sera envoyé.
        </p>
        <form method="POST"
              action="{{ route('pos.sessions.verify-access', $activeSession) }}"
              data-verify-url="{{ route('pos.sessions.verify-access', $activeSession) }}"
              x-data
              @submit.prevent="window.verifyPosSessionAccess($el)">
            @csrf
            <div class="form-group">
                <label for="verification_code">Code reçu à l’ouverture</label>
                <input id="verification_code" name="verification_code" type="text"
                       inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                       autocomplete="one-time-code" class="form-control" placeholder="Code à 6 chiffres" required autofocus>
            </div>
            <button type="submit" class="btn btn--primary" style="width:100%;justify-content:center;">
                Vérifier et accéder au POS
            </button>
        </form>
    </div>
</div>
@endsection