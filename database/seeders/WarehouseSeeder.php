<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            ['name' => 'Boutique Principale', 'code' => 'BTQ01', 'address' => 'Cameroun, Centre-Obala Espace Mokolo', 'phone' => ''],
        ];

        foreach ($warehouses as $w) {
            $warehouse = Warehouse::firstOrCreate(['code' => $w['code']], array_merge($w, ['is_active' => true]));
        }

        if (isset($warehouse)) {
            Setting::set('default_warehouse_id', $warehouse->id, 'general');
        }
    }
}
