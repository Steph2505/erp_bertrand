<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\PaymentAccount;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // ── Référentiels ──────────────────────────────────────────────────
            RoleSeeder::class,
            PermissionSeeder::class,
            // UserSeeder 
            UserSeeder::class,
            SettingSeeder::class,
            UnitSeeder::class,
            CategorySeeder::class,
            CustomerGroupSeeder::class,

            // ── Lieux & Matériel ──────────────────────────────────────────────
            WarehouseSeeder::class,
            CaisseSeeder::class,
            // PosPaginationProductSeeder::class,

            // ── Contacts ──────────────────────────────────────────────────────
            // SupplierSeeder et CustomerSeeder créent des contacts génériques de démonstration.
            SupplierSeeder::class,
            CustomerSeeder::class,

            // ── Catalogue ─────────────────────────────────────────────────────
            // Données de démonstration : ne pas activer en production.
            // ProductSeeder::class,
            // PackSeeder::class,

            // ── Finance ───────────────────────────────────────────────────────
            PaymentAccountSeeder::class,
            ExpenseCategorySeeder::class,

            // ── Données transactionnelles ─────────────────────────────────────
            // PurchaseSeeder::class,
            // SaleSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('✅  Base de données Bertrand Store initialisée !');
        $this->command->table(
            ['Entité', 'Nb'],
            [
                ['Utilisateurs',       User::count()],
                // ['Produits',           \App\Models\Product::count()],
                // ['Packs',              \App\Models\Pack::count()],
                ['Clients',            Customer::count()],
                ['Fournisseurs',       Supplier::count()],
                // ['Achats',             \App\Models\Purchase::count()],
                // ['Ventes',             \App\Models\Sale::count()],
                // ['Dépenses',           \App\Models\Expense::count()],
                ['Comptes paiement',   PaymentAccount::count()],
            ]
        );
        $this->command->newLine();
        $this->command->line('  Créez ou vérifiez les comptes utilisateurs via le processus sécurisé de déploiement.');
    }
}
