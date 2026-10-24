<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Administrateur',      'email' => 'admin@mokolo.com',      'role' => 'Super Admin'],
            ['name' => 'Caissier Test 1',     'email' => 'caissier1@mokolo.com',  'role' => 'Caissier'],
            ['name' => 'Caissier Test 2',     'email' => 'caissier2@mokolo.com',  'role' => 'Caissier'],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make('password'), 'is_active' => true]
            );
            $user->syncRoles([$data['role']]);
        }
    }
}
