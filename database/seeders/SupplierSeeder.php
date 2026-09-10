<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['name' => 'Fournisseur divers', 'email' => 'fourdivers@gmail.com',      'phone' => '', 'address' => 'Cameroun'],
        ];

        foreach ($suppliers as $s) {
            Supplier::firstOrCreate(['email' => $s['email']], array_merge($s, ['is_active' => true]));
        }
    }
}
