<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PosSaleHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_sees_only_their_pos_sales_and_admin_sees_all_sales(): void
    {
        $managePos = Permission::firstOrCreate([
            'name' => 'manage pos',
            'guard_name' => 'web',
        ]);

        $cashier = User::factory()->create(['is_active' => true]);
        $cashier->givePermissionTo($managePos);
        $otherCashier = User::factory()->create(['is_active' => true]);
        $ownSale = $this->createPosSale($cashier);
        $otherSale = $this->createPosSale($otherCashier);

        $this->actingAs($cashier)
            ->getJson(route('pos.api.list'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownSale->id)
            ->assertJsonPath('data.0.date_time', $ownSale->created_at->format('d/m/Y H:i'));

        $this->get(route('pos.show-ref', $otherSale->reference))->assertNotFound();
        $this->get(route('pos.receipt', $otherSale))->assertNotFound();
        $this->postJson(route('pos.pay', $otherSale))->assertNotFound();

        $adminRole = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);
        $adminRole->givePermissionTo($managePos);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole($adminRole);

        $this->actingAs($admin)
            ->getJson(route('pos.api.list'))
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    private function createPosSale(User $creator): Sale
    {
        return Sale::create([
            'reference' => 'POS-TEST-' . $creator->id,
            'sale_date' => now()->toDateString(),
            'is_pos' => true,
            'created_by' => $creator->id,
        ]);
    }
}
