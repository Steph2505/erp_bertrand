<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserSpecificReportPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_permission_is_assigned_only_to_individual_users(): void
    {
        $manageUsers = Permission::create(['name' => 'manage users', 'guard_name' => 'web']);
        $viewReports = Permission::create(['name' => 'view reports', 'guard_name' => 'web']);
        $managePos = Permission::create(['name' => 'manage pos', 'guard_name' => 'web']);

        $cashierRole = Role::create(['name' => 'Caissier', 'guard_name' => 'web']);
        $cashierRole->givePermissionTo($managePos);

        $manager = User::factory()->create(['is_active' => true]);
        $manager->givePermissionTo($manageUsers);

        $cashier = User::factory()->create(['is_active' => true]);
        $cashier->assignRole($cashierRole);

        $anotherCashier = User::factory()->create(['is_active' => true]);
        $anotherCashier->assignRole($cashierRole);

        $this->assertFalse($cashierRole->fresh()->hasPermissionTo($viewReports));
        $this->assertFalse($cashier->can('view reports'));

        $this->actingAs($manager)
            ->put(route('users.update', $cashier), [
                'name' => $cashier->name,
                'email' => $cashier->email,
                'role' => $cashierRole->name,
                'permissions' => ['manage pos', 'view reports'],
            ])
            ->assertRedirect();

        $this->assertTrue($cashier->fresh()->can('view reports'));
        $this->assertFalse($anotherCashier->fresh()->can('view reports'));

        $this->actingAs($cashier->fresh())
            ->get(route('reports.tax'))
            ->assertOk();

        $this->actingAs($manager)
            ->put(route('users.update', $cashier), [
                'name' => $cashier->name,
                'email' => $cashier->email,
                'role' => $cashierRole->name,
                'permissions' => ['manage pos'],
            ])
            ->assertRedirect();

        $this->assertFalse($cashier->fresh()->can('view reports'));

        $this->actingAs($cashier->fresh())
            ->get(route('reports.tax'))
            ->assertForbidden();

        $this->actingAs($cashier->fresh())
            ->get(route('pos.list'))
            ->assertOk()
            ->assertDontSee('STOCK')
            ->assertDontSee('FINANCE')
            ->assertDontSee('CONFIG');
    }
}
