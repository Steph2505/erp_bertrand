<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Throwable;

class ResetPasswordController extends Controller
{
    public function showForm(Request $request, string $token): View
    {
        try {
            return view('auth.reset-password', [
                'token' => $token,
                'email' => $request->email,
            ]);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la page de réinitialisation');
            throw $e;
        }
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()->symbols()],
        ], [
            'password.min'      => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.mixed'    => 'Le mot de passe doit contenir au moins une majuscule et une minuscule.',
            'password.numbers'  => 'Le mot de passe doit contenir au moins un chiffre.',
            'password.symbols'  => 'Le mot de passe doit contenir au moins un caractère spécial.',
        ]);

        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->forceFill(['password' => Hash::make($password)])->setRememberToken(Str::random(60));
                    $user->save();
                    event(new PasswordReset($user));
                }
            );
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la réinitialisation du mot de passe', ['email' => $request->email]);
            return back()->withErrors(['email' => 'Une erreur est survenue lors de la réinitialisation.']);
        }

        // L'app n'a pas de fichiers de traduction (lang/), donc __($status) renverrait
        // la clé brute ("passwords.token"...) au lieu d'un message lisible.
        $messages = [
            Password::PASSWORD_RESET  => 'Mot de passe réinitialisé avec succès.',
            Password::INVALID_USER    => 'Aucun compte ne correspond à cette adresse email.',
            Password::INVALID_TOKEN   => 'Ce lien de réinitialisation est invalide ou a expiré.',
        ];
        $message = $messages[$status] ?? 'Une erreur est survenue lors de la réinitialisation.';

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', $message)
            : back()->withErrors(['email' => $message]);
    }
}
