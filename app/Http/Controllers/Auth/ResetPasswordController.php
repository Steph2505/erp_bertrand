<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
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
            'token'                 => 'required',
            'email'                 => 'required|email',
            'password'              => 'required|min:8|confirmed',
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

        return $status === Password::PasswordReset
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
