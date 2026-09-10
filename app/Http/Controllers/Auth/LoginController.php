<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class LoginController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        try {
            if (Auth::check()) {
                return redirect()->route('dashboard');
            }
            return view('auth.login');
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement de la page de connexion');
            throw $e;
        }
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        try {
            if (Auth::attempt($credentials, $request->boolean('remember'))) {
                $request->session()->regenerate();
                Auth::user()->update(['last_login_at' => now()]);
                return redirect()->intended(route('dashboard'));
            }
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la tentative de connexion', ['email' => $credentials['email']]);
            return back()->withErrors(['email' => 'Une erreur est survenue lors de la connexion.'])->onlyInput('email');
        }

        return back()->withErrors([
            'email' => 'Les identifiants fournis ne correspondent à aucun compte.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        try {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la déconnexion');
        }

        return redirect()->route('login');
    }
}
