<?php

namespace Database\Seeders;

use App\Models\CustomerGroup;
use App\Models\PriceGroup;
use Illuminate\Database\Seeder;

class CustomerGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['name' => 'Particulier',   'discount' => 0],
            ['name' => 'Professionnel', 'discount' => 10],
            ['name' => 'Revendeur',     'discount' => 15],
            ['name' => 'Fidèle',        'discount' => 5],
        ];

        foreach ($groups as $g) {
            CustomerGroup::firstOrCreate(['name' => $g['name']], ['discount' => $g['discount']]);
        }

        $priceGroups = [
            ['name' => 'Prix Pro',       'description' => 'Tarif professionnel'],
            ['name' => 'Prix Revendeur', 'description' => 'Tarif revendeur bulk'],
            ['name' => 'Prix Fidèle',    'description' => 'Tarif client fidèle'],
        ];

        foreach ($priceGroups as $pg) {
            PriceGroup::firstOrCreate(['name' => $pg['name']], ['description' => $pg['description']]);
        }
    }
}
