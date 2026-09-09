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
            'manage sales',
            'manage pos',
            'manage stock',
            'manage customers',
            'manage suppliers',
            'manage expenses',
            'view reports',
            'manage accounts',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $map = [
            'Admin'      => ['manage products', 'manage purchases', 'manage sales', 'manage pos', 'manage stock', 'manage customers', 'manage suppliers', 'manage expenses', 'view reports', 'manage accounts'],
            'Caissier'   => ['manage sales', 'manage pos', 'manage customers', 'view reports'],
            'Magasinier' => ['manage products', 'manage purchases', 'manage stock', 'manage suppliers', 'view reports'],
            'Comptable'  => ['manage purchases', 'manage expenses', 'manage accounts', 'manage suppliers', 'view reports'],
            'Commercial' => ['manage sales', 'manage pos', 'manage products', 'manage customers', 'view reports'],
        ];

        foreach ($map as $roleName => $perms) {
            $role = Role::findByName($roleName, 'web');
            if ($role) {
                $role->syncPermissions($perms);
            }
        }

        // Super Admin reçoit TOUTES les permissions
        $superAdmin = Role::findByName('Super Admin', 'web');
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }
    }
}
