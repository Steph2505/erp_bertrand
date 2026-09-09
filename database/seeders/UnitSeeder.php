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
            ['name' => 'Kilogramme', 'abbreviation' => 'kg'],
            ['name' => 'Gramme',     'abbreviation' => 'g'],
            ['name' => 'Litre',      'abbreviation' => 'L'],
            ['name' => 'Centilitre', 'abbreviation' => 'cl'],
            ['name' => 'Boîte',      'abbreviation' => 'bte'],
            ['name' => 'ballot',     'abbreviation' => 'blt'],
            ['name' => 'Plateau',    'abbreviation' => 'plat'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(['name' => $unit['name']], ['abbreviation' => $unit['abbreviation']]);
        }
    }
}
