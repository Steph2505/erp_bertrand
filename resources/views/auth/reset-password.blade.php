@extends('layouts.auth')
@section('title', 'Nouveau mot de passe')

@section('content')
<div class="auth-standalone">
    <div class="auth-standalone__card">
        <div class="auth-standalone__header">
            <h2 class="auth-standalone__title">Nouveau mot de passe</h2>
            <p class="auth-standalone__subtitle">Créez un mot de passe sécurisé pour votre compte.</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" x-data="passwordStrength()">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control @error('email') form-control--error @enderror" value="{{ old('email', $email) }}" required>
                @error('email') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="password">Nouveau mot de passe <span class="required">*</span></label>
                <input type="password" id="password" name="password" class="form-control @error('password') form-control--error @enderror" x-on:input="checkStrength($el.value)" required minlength="8">
                <div class="password-strength" x-show="strength > 0">
                    <div class="password-strength__bar">
                        <div class="password-strength__bar-fill" :style="`width:${strength}%;background:${color}`"></div>
                    </div>
                    <span class="password-strength__label" :class="labelClass" x-text="label"></span>
                </div>
                @error('password') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirmer le mot de passe <span class="required">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
            </div>

            <button type="submit" class="btn btn--primary btn--block w-full">Réinitialiser le mot de passe</button>
        </form>
    </div>
</div>

<script>
function passwordStrength() {
    return {
        strength: 0, label: '', color: '', labelClass: '',
        checkStrength(val) {
            let s = 0;
            if (val.length >= 8)   s += 25;
            if (/[A-Z]/.test(val)) s += 25;
            if (/[0-9]/.test(val)) s += 25;
            if (/[^A-Za-z0-9]/.test(val)) s += 25;
            this.strength = s;
            if (s <= 25)      { this.label = 'Faible';  this.color = '#EF4444'; this.labelClass = 'password-strength__label--weak'; }
            else if (s <= 50) { this.label = 'Moyen';   this.color = '#F59E0B'; this.labelClass = 'password-strength__label--medium'; }
            else              { this.label = 'Fort';    this.color = '#22C55E'; this.labelClass = 'password-strength__label--strong'; }
        }
    }
}
</script>
@endsection
