<?php

namespace Database\Seeders;

use App\Models\PaymentAccount;
use Illuminate\Database\Seeder;

class PaymentAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['name' => 'Caisse Principale',  'type' => 'cash',         'opening_balance' => 0, 'is_default' => true],
            ['name' => 'Banque',        'type' => 'bank',         'opening_balance' => 0, 'is_default' => false],
            ['name' => 'Mobile Money',   'type' => 'mobile_money', 'opening_balance' => 0,  'is_default' => false],
            ['name' => 'Orange Money',            'type' => 'orange_money', 'opening_balance' => 0,  'is_default' => false],
        ];

        foreach ($accounts as $a) {
            PaymentAccount::firstOrCreate(['name' => $a['name']], [
                'type'            => $a['type'],
                'opening_balance' => $a['opening_balance'],
                'current_balance' => $a['opening_balance'],
                'is_default'      => $a['is_default'],
                'is_active'       => true,
            ]);
        }
    }
}
