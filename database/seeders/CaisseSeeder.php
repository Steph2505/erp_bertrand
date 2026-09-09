<?php

namespace Database\Seeders;

use App\Models\Caisse;
use Illuminate\Database\Seeder;

class CaisseSeeder extends Seeder
{
    public function run(): void
    {
        $caisses = [
            ['name' => 'Caisse Boutique Principale', 'description' => 'Caisse principale boutique'],
        ];

        foreach ($caisses as $c) {
            Caisse::firstOrCreate(['name' => $c['name']], array_merge($c, ['is_active' => true]));
        }
    }
}
