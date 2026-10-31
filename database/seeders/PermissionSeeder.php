<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage users',
            'manage settings',
            'manage products',
            'manage purchases',
            'manage pos',
            'create caisses',
            'close pos sessions',
            'manage stock',
            'manage customers',
            'manage suppliers',
            'manage expenses',
            'view reports',
            'manage accounts',
            'receive notifications',
            'view financial dashboard',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $map = [
            'Admin'      => ['manage products', 'manage purchases', 'manage pos', 'create caisses', 'close pos sessions', 'manage stock', 'manage customers', 'manage suppliers', 'manage expenses', 'manage accounts', 'receive notifications', 'view financial dashboard'],
            'Caissier'   => ['manage pos', 'close pos sessions', 'manage customers'],
            'Magasinier' => ['manage products', 'manage purchases', 'manage stock', 'manage suppliers'],
            'Comptable'  => ['manage purchases', 'manage expenses', 'manage accounts', 'manage suppliers'],
            'Commercial' => ['manage pos', 'close pos sessions', 'manage products', 'manage customers'],
        ];

        foreach ($map as $roleName => $perms) {
            $role = Role::findByName($roleName, 'web');
            if ($role) {
                $role->syncPermissions($perms);
            }
        }

        // Super Admin bypasses permission checks through the authorization gate.
        $superAdmin = Role::findByName('Super Admin', 'web');
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::where('name', '!=', 'view reports')->get());
        }
    }
}
