<?php

namespace Tests\Feature;

use App\Models\Caisse;
use App\Models\PosSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PosSessionPinTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_sets_hashed_pin_when_opening_a_session_and_uses_it_again(): void
    {
        [$user, $caisse] = $this->cashierWithCaisse();

        $this->actingAs($user)
            ->postJson(route('pos.sessions.open'), [
                'caisse_id' => $caisse->id,
                'opening_balance' => 100,
                'pin' => '012345',
                'pin_confirmation' => '012345',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $user->refresh();
        $this->assertNotSame('012345', $user->pos_pin_hash);
        $this->assertTrue(Hash::check('012345', $user->pos_pin_hash));

        $session = PosSession::where('user_id', $user->id)->firstOrFail();

        $this->postJson(route('pos.sessions.verify-access', $session), [
            'pin' => '999999',
        ])->assertUnprocessable()->assertJsonPath('message', 'PIN incorrect.');

        $this->postJson(route('pos.sessions.verify-access', $session), [
            'pin' => '012345',
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_mismatched_initial_pin_confirmation_does_not_set_pin_or_open_session(): void
    {
        [$user, $caisse] = $this->cashierWithCaisse();

        $this->actingAs($user)
            ->postJson(route('pos.sessions.open'), [
                'caisse_id' => $caisse->id,
                'opening_balance' => 100,
                'pin' => '012345',
                'pin_confirmation' => '543210',
            ])
            ->assertUnprocessable();

        $this->assertNull($user->fresh()->pos_pin_hash);
        $this->assertDatabaseCount('pos_sessions', 0);
    }

    public function test_user_manager_can_reset_cashier_pin(): void
    {
        $manager = User::factory()->create(['is_active' => true]);
        $manager->givePermissionTo(Permission::firstOrCreate([
            'name' => 'manage users',
            'guard_name' => 'web',
        ]));
        $cashier = User::factory()->create(['is_active' => true]);
        $cashier->forceFill(['pos_pin_hash' => Hash::make('123456')])->save();

        $this->actingAs($manager)
            ->delete(route('users.reset-pin', $cashier))
            ->assertRedirect();

        $this->assertNull($cashier->fresh()->pos_pin_hash);
    }

    private function cashierWithCaisse(): array
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(Permission::firstOrCreate([
            'name' => 'manage pos',
            'guard_name' => 'web',
        ]));
        $caisse = Caisse::create([
            'name' => 'Caisse test',
            'manager_id' => $user->id,
        ]);

        return [$user, $caisse];
    }
}
