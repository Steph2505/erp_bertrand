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
            ['name' => 'Détaillant', 'discount' => 0, 'is_wholesale' => false],
            ['name' => 'Grossiste',  'discount' => 0, 'is_wholesale' => true],
        ];

        foreach ($groups as $g) {
            CustomerGroup::firstOrCreate(['name' => $g['name']], [
                'discount'     => $g['discount'],
                'is_wholesale' => $g['is_wholesale'],
            ]);
        }

        $priceGroups = [
            ['name' => 'Prix Pro',       'description' => 'Tarif professionnel'],
            ['name' => 'Prix Revendeur', 'description' => 'Tarif revendeur bulk']
        ];

        foreach ($priceGroups as $pg) {
            PriceGroup::firstOrCreate(['name' => $pg['name']], ['description' => $pg['description']]);
        }
    }
}
