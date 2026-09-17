<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Observers\ActivityLogObserver;
use App\Observers\ProductObserver;
use Illuminate\Pagination\Paginator;
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
        Product::observe(ProductObserver::class);

        // Journal d'activité : trace la création/modification/suppression
        // des principaux modèles métier (voir rapports > Journal d'activité).
        foreach ([Product::class, Category::class, Unit::class, Customer::class, Supplier::class, Sale::class, Purchase::class, User::class] as $model) {
            $model::observe(ActivityLogObserver::class);
        }

        // Vue de pagination alignée sur le design système custom (BEM/SCSS)
        // au lieu de la vue Tailwind par défaut de Laravel, non chargée ici.
        Paginator::defaultView('vendor.pagination.custom');

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
