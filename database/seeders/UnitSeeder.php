<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Pièce',      'abbreviation' => 'pcs'],
            ['name' => 'carton', 'abbreviation' => 'ctn'],
            ['name' => 'Gramme',     'abbreviation' => 'g'],
            ['name' => 'Litre',      'abbreviation' => 'L'],
            ['name' => 'Boîte',      'abbreviation' => 'bte'],
            ['name' => 'ballot',     'abbreviation' => 'blt'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(['name' => $unit['name']], ['abbreviation' => $unit['abbreviation']]);
        }
    }
}
