<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PosProductPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_catalog_returns_fifteen_products_per_page(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(Permission::create([
            'name' => 'manage pos',
            'guard_name' => 'web',
        ]));

        foreach (range(1, 16) as $number) {
            $name = 'POS Pagination Product ' . $number;
            Product::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'selling_price' => 1000,
                'stock_quantity' => 10,
                'is_active' => true,
            ]);
        }

        $this->actingAs($user)
            ->getJson(route('pos.search-items', ['q' => 'POS Pagination Product', 'page' => 1]))
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('last_page', 2)
            ->assertJsonPath('total', 16);

        $this->getJson(route('pos.search-items', ['q' => 'POS Pagination Product', 'page' => 2]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('current_page', 2);
    }
}
