<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Throwable;

class ForgotPasswordController extends Controller
{
    public function showForm(): View
    {
        try {
            return view('auth.forgot-password');
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la page de mot de passe oublié');
            throw $e;
        }
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de l\'envoi du lien de réinitialisation', ['email' => $request->email]);
            return back()->withErrors(['email' => 'Une erreur est survenue lors de l\'envoi du lien.']);
        }

        // L'app n'a pas de fichiers de traduction (lang/), donc __($status) renverrait
        // la clé brute ("passwords.sent"...) au lieu d'un message lisible.
        $messages = [
            Password::RESET_LINK_SENT  => 'Un lien de réinitialisation a été envoyé à votre adresse email.',
            Password::INVALID_USER     => 'Aucun compte ne correspond à cette adresse email.',
            Password::RESET_THROTTLED  => 'Veuillez patienter avant de réessayer.',
        ];
        $message = $messages[$status] ?? 'Une erreur est survenue lors de l\'envoi du lien.';

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', $message)
            : back()->withErrors(['email' => $message]);
    }
}
