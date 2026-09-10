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

        return $status === Password::ResetLinkSent
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
