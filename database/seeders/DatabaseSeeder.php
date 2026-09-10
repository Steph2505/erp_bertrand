<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // ── Référentiels ──────────────────────────────────────────────────
            RoleSeeder::class,
            PermissionSeeder::class,
            UserSeeder::class,
            SettingSeeder::class,
            UnitSeeder::class,
            CategorySeeder::class,
            CustomerGroupSeeder::class,

            // ── Lieux & Matériel ──────────────────────────────────────────────
            WarehouseSeeder::class,
            CaisseSeeder::class,

            // ── Contacts ──────────────────────────────────────────────────────
            SupplierSeeder::class,
            CustomerSeeder::class,

            // ── Catalogue ─────────────────────────────────────────────────────
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
                ['Utilisateurs',       \App\Models\User::count()],
                // ['Produits',           \App\Models\Product::count()],
                // ['Packs',              \App\Models\Pack::count()],
                ['Clients',            \App\Models\Customer::count()],
                ['Fournisseurs',       \App\Models\Supplier::count()],
                // ['Achats',             \App\Models\Purchase::count()],
                // ['Ventes',             \App\Models\Sale::count()],
                // ['Dépenses',           \App\Models\Expense::count()],
                ['Comptes paiement',   \App\Models\PaymentAccount::count()],
            ]
        );
        $this->command->newLine();
        $this->command->line('  Connexion admin : <fg=green>admin@bertrand-store.com</> / <fg=yellow>password</>');
    }
}
