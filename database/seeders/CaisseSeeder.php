<?php

namespace Database\Seeders;

use App\Models\Caisse;
use Illuminate\Database\Seeder;

class CaisseSeeder extends Seeder
{
    public function run(): void
    {
        $caisses = [
            ['name' => 'Caisse A', 'description' => 'Caisse principale boutique'],
            ['name' => 'Caisse B', 'description' => 'Caisse secondaire boutique'],
            ['name' => 'Caisse C', 'description' => 'Caisse de caisse'],
        ];

        foreach ($caisses as $c) {
            Caisse::firstOrCreate(['name' => $c['name']], array_merge($c, ['is_active' => true]));
        }
    }
}
