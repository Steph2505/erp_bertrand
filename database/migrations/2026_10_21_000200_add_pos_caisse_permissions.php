<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $createCaisse = Permission::firstOrCreate(['name' => 'create caisses', 'guard_name' => 'web']);
        $closeSession = Permission::firstOrCreate(['name' => 'close pos sessions', 'guard_name' => 'web']);

        foreach (['Admin', 'Super Admin'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->givePermissionTo([$createCaisse, $closeSession]);
        }

        foreach (['Caissier', 'Commercial'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->givePermissionTo($closeSession);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permissions = Permission::whereIn('name', ['create caisses', 'close pos sessions'])
            ->where('guard_name', 'web')
            ->get();

        foreach ($permissions as $permission) {
            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};