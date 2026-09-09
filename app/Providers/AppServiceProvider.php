<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Charge la devise depuis la table settings une seule fois par requête.
        // Le try/catch protège contre un boot avant que les migrations soient jouées.
        try {
            $currency       = \App\Models\Setting::get('currency', 'XOF');
            $currencySymbol = \App\Models\Setting::get('currency_symbol', $currency);
            config([
                'app.currency'        => $currency,
                'app.currency_symbol' => $currencySymbol,
            ]);
            View::share('currency', $currencySymbol);
        } catch (\Throwable) {
            // Table settings inexistante (première migration) → valeurs par défaut conservées
        }
    }
}
