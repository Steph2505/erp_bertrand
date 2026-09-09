<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Loyer & Charges locatives',
            'Salaires & Charges sociales',
            'Eau & Électricité',
            'Transport & Livraison',
            'Matières premières',
            'Emballages & Consommables',
            'Entretien & Réparations',
            'Marketing & Publicité',
            'Téléphone & Internet',
            'Frais bancaires',
            'Impôts & Taxes',
            'Formation & Développement',
            'Autres dépenses',
        ];

        foreach ($categories as $name) {
            ExpenseCategory::firstOrCreate(['name' => $name]);
        }
    }
}
