<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentAccount;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $caisse = PaymentAccount::where('is_default', true)->first();
        $bank   = PaymentAccount::where('type', 'bank')->first();

        $cats = ExpenseCategory::all()->keyBy('name');

        $rows = [
            ['cat' => 'Loyer & Charges locatives',    'amount' => 250000, 'desc' => 'Loyer boutique Plateau',       'date' => now()->subDays(85), 'account' => $bank],
            ['cat' => 'Eau & Électricité',            'amount' => 45000,  'desc' => 'Facture SODECI – Mars',        'date' => now()->subDays(82), 'account' => $caisse],
            ['cat' => 'Téléphone & Internet',         'amount' => 25000,  'desc' => 'Abonnement fibre Orange CI',   'date' => now()->subDays(80), 'account' => $caisse],
            ['cat' => 'Transport & Livraison',        'amount' => 35000,  'desc' => 'Livraison commandes clients',  'date' => now()->subDays(70), 'account' => $caisse],
            ['cat' => 'Salaires & Charges sociales',  'amount' => 450000, 'desc' => 'Salaires Mars 2026',           'date' => now()->subDays(65), 'account' => $bank],
            ['cat' => 'Emballages & Consommables',    'amount' => 28000,  'desc' => 'Boîtes et sachets kraft',      'date' => now()->subDays(55), 'account' => $caisse],
            ['cat' => 'Entretien & Réparations',      'amount' => 60000,  'desc' => 'Révision four pâtisserie',     'date' => now()->subDays(48), 'account' => $caisse],
            ['cat' => 'Loyer & Charges locatives',    'amount' => 250000, 'desc' => 'Loyer boutique Plateau',       'date' => now()->subDays(55), 'account' => $bank],
            ['cat' => 'Eau & Électricité',            'amount' => 52000,  'desc' => 'Facture CIE – Avril',          'date' => now()->subDays(50), 'account' => $caisse],
            ['cat' => 'Marketing & Publicité',        'amount' => 75000,  'desc' => 'Campagne réseaux sociaux',     'date' => now()->subDays(40), 'account' => $bank],
            ['cat' => 'Salaires & Charges sociales',  'amount' => 450000, 'desc' => 'Salaires Avril 2026',          'date' => now()->subDays(35), 'account' => $bank],
            ['cat' => 'Transport & Livraison',        'amount' => 40000,  'desc' => 'Carburant et livraisons',      'date' => now()->subDays(28), 'account' => $caisse],
            ['cat' => 'Téléphone & Internet',         'amount' => 25000,  'desc' => 'Abonnement fibre Orange CI',   'date' => now()->subDays(25), 'account' => $caisse],
            ['cat' => 'Emballages & Consommables',    'amount' => 32000,  'desc' => 'Ruban adhésif et étiquettes', 'date' => now()->subDays(20), 'account' => $caisse],
            ['cat' => 'Loyer & Charges locatives',    'amount' => 250000, 'desc' => 'Loyer boutique Plateau',       'date' => now()->subDays(25), 'account' => $bank],
            ['cat' => 'Eau & Électricité',            'amount' => 48000,  'desc' => 'Facture SODECI – Mai',         'date' => now()->subDays(15), 'account' => $caisse],
            ['cat' => 'Impôts & Taxes',               'amount' => 85000,  'desc' => 'Patente trimestrielle',        'date' => now()->subDays(12), 'account' => $bank],
            ['cat' => 'Salaires & Charges sociales',  'amount' => 450000, 'desc' => 'Salaires Mai 2026',            'date' => now()->subDays(5),  'account' => $bank],
            ['cat' => 'Frais bancaires',              'amount' => 8500,   'desc' => 'Frais tenue de compte SGBCI',  'date' => now()->subDays(3),  'account' => $bank],
        ];

        $counter = 1;
        foreach ($rows as $row) {
            $cat = $cats[$row['cat']] ?? null;
            $ref = 'EXP-' . $row['date']->format('Ymd') . '-' . str_pad($counter++, 4, '0', STR_PAD_LEFT);

            Expense::create([
                'reference'          => $ref,
                'expense_category_id' => $cat?->id,
                'payment_account_id' => $row['account']?->id,
                'expense_date'       => $row['date']->toDateString(),
                'amount'             => $row['amount'],
                'description'        => $row['desc'],
                'created_by'         => 1,
            ]);

            if ($row['account']) {
                $row['account']->decrement('current_balance', $row['amount']);
            }
        }
    }
}
