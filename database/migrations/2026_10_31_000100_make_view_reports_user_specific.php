<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::where('name', 'view reports')
            ->where('guard_name', 'web')
            ->first();

        if ($permission) {
            DB::table('role_has_permissions')
                ->where('permission_id', $permission->id)
                ->delete();
        }

        Schema::dropIfExists('user_denied_permissions');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::create('user_denied_permissions', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['user_id', 'permission_id']);
        });

        $permission = Permission::where('name', 'view reports')
            ->where('guard_name', 'web')
            ->first();

        if ($permission) {
            foreach (['Admin', 'Caissier', 'Magasinier', 'Comptable', 'Commercial', 'Super Admin'] as $roleName) {
                Role::where('name', $roleName)
                    ->where('guard_name', 'web')
                    ->first()
                    ?->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
