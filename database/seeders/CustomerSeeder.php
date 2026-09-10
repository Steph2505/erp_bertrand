<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerGroup;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $gpPart = CustomerGroup::where('name', 'Détaillant')->first();

        $customers = [
            ['name' => 'Client divers',       'email' => 'clientdivers@gmail.com',    'phone' => '', 'group' => $gpPart],
        ];

        foreach ($customers as $c) {
            Customer::firstOrCreate(['email' => $c['email']], [
                'name'              => $c['name'],
                'phone'             => $c['phone'],
                'customer_group_id' => $c['group']?->id,
                'is_active'         => true,
            ]);
        }
    }
}
