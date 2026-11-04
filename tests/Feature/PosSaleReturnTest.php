<?php

namespace Tests\Feature;

use App\Helpers\ProfitHelper;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PosSaleReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_selected_quantities_and_reduces_only_the_selected_stock(): void
    {
        $user = $this->posUser();
        $user->assignRole(Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']));
        $account = PaymentAccount::create([
            'name' => 'Caisse retour',
            'type' => 'cash',
            'current_balance' => 100,
        ]);
        $firstProduct = $this->product('Article un', 4);
        $secondProduct = $this->product('Article deux', 7);
        $sale = $this->sale($user);
        $firstLine = $this->saleLine($sale, $firstProduct, 'Article un', 2, 50);
        $secondLine = $this->saleLine($sale, $secondProduct, 'Article deux', 1, 30);

        $this->actingAs($user)
            ->get(route('pos.returns.create', ['sale_id' => $sale->id]))
            ->assertOk()
            ->assertSee('Article un');

        $this->actingAs($user)
            ->post(route('pos.returns.store'), [
                'sale_id' => $sale->id,
                'return_date' => now()->toDateString(),
                'payment_method' => 'cash',
                'payment_account_id' => $account->id,
                'items' => [
                    $firstLine->id => ['quantity' => 1, 'stock_condition' => 'resellable'],
                    $secondLine->id => ['quantity' => 0, 'stock_condition' => 'resellable'],
                ],
            ])
            ->assertRedirect(route('pos.returns.index'))
            ->assertSessionHas('success');

        $return = SaleReturn::with('items')->firstOrFail();
        $this->assertSame(25.0, (float) $return->total);
        $this->assertCount(1, $return->items);
        $this->assertSame(1, $return->items->first()->quantity);
        $this->assertSame(5, $firstProduct->fresh()->stock_quantity);
        $this->assertSame(7, $secondProduct->fresh()->stock_quantity);
        $this->assertSame(75.0, (float) $account->fresh()->current_balance);

        $payment = Payment::firstOrFail();
        $this->assertSame(SaleReturn::class, $payment->payable_type);
        $this->assertSame((string) $return->id, (string) $payment->payable_id);
        $this->assertSame(25.0, (float) $payment->amount);
    }

    public function test_it_rejects_returns_above_the_remaining_quantity(): void
    {
        $user = $this->posUser();
        $product = $this->product('Article limité', 3);
        $sale = $this->sale($user);
        $line = $this->saleLine($sale, $product, 'Article limité', 2, 50);

        SaleReturn::create([
            'reference' => 'RET-EXISTANT',
            'sale_id' => $sale->id,
            'return_date' => now()->toDateString(),
            'total' => 25,
        ]);
        SaleReturnItem::create([
            'sale_return_id' => SaleReturn::firstOrFail()->id,
            'sale_item_id' => $line->id,
            'item_name' => $line->item_name,
            'quantity' => 1,
            'total' => 25,
            'stock_condition' => 'damaged',
        ]);

        $this->actingAs($user)
            ->from(route('pos.returns.create', ['sale_id' => $sale->id]))
            ->post(route('pos.returns.store'), [
                'sale_id' => $sale->id,
                'return_date' => now()->toDateString(),
                'payment_method' => 'cash',
                'items' => [
                    $line->id => ['quantity' => 2, 'stock_condition' => 'resellable'],
                ],
            ])
            ->assertRedirect(route('pos.returns.create', ['sale_id' => $sale->id]))
            ->assertSessionHasErrors("items.{$line->id}.quantity");

        $this->assertDatabaseCount('sale_returns', 1);
        $this->assertSame(3, $product->fresh()->stock_quantity);
    }

    public function test_it_blocks_a_new_return_when_legacy_returns_have_no_item_details(): void
    {
        $user = $this->posUser();
        $sale = $this->sale($user);
        $line = $this->saleLine($sale, $this->product('Article historique', 2), 'Article historique', 1, 80);

        SaleReturn::create([
            'reference' => 'RET-ANCIEN',
            'sale_id' => $sale->id,
            'return_date' => now()->toDateString(),
            'total' => 20,
        ]);

        $this->actingAs($user)
            ->from(route('pos.returns.create', ['sale_id' => $sale->id]))
            ->post(route('pos.returns.store'), [
                'sale_id' => $sale->id,
                'return_date' => now()->toDateString(),
                'payment_method' => 'cash',
                'items' => [
                    $line->id => ['quantity' => 1, 'stock_condition' => 'resellable'],
                ],
            ])
            ->assertRedirect(route('pos.returns.create', ['sale_id' => $sale->id]))
            ->assertSessionHasErrors('sale_id');

        $this->assertDatabaseCount('sale_returns', 1);
        $this->assertDatabaseCount('sale_return_items', 0);
    }

    public function test_non_super_admin_users_can_only_see_and_return_their_own_sales(): void
    {
        $otherUser = $this->posUser();
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']));
        $admin->givePermissionTo(Permission::firstOrCreate([
            'name' => 'manage pos',
            'guard_name' => 'web',
        ]));

        $ownSale = $this->sale($admin, 'POS-ADMIN-OWN');
        $otherSale = $this->sale($otherUser, 'POS-OTHER');
        $this->saleLine($ownSale, $this->product('Article propre', 2), 'Article propre', 1, 80);
        $otherLine = $this->saleLine($otherSale, $this->product('Article autre', 2), 'Article autre', 1, 80);

        $this->actingAs($admin)
            ->get(route('pos.returns.create'))
            ->assertOk()
            ->assertSee('POS-ADMIN-OWN')
            ->assertDontSee('POS-OTHER');

        $this->actingAs($admin)
            ->get(route('pos.returns.create', ['sale_id' => $otherSale->id]))
            ->assertNotFound();

        $this->actingAs($admin)
            ->post(route('pos.returns.store'), [
                'sale_id' => $otherSale->id,
                'return_date' => now()->toDateString(),
                'payment_method' => 'cash',
                'items' => [
                    $otherLine->id => ['quantity' => 1, 'stock_condition' => 'resellable'],
                ],
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('sale_returns', 0);
    }

    public function test_only_super_admin_can_see_sales_created_by_other_users(): void
    {
        $owner = $this->posUser();
        $sale = $this->sale($owner, 'POS-OWNER-SALE');
        $this->saleLine($sale, $this->product('Article super admin', 1), 'Article super admin', 1, 80);

        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole(Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']));

        $this->actingAs($superAdmin)
            ->get(route('pos.returns.create'))
            ->assertOk()
            ->assertSee('POS-OWNER-SALE');

        $this->actingAs($superAdmin)
            ->get(route('pos.returns.create', ['sale_id' => $sale->id]))
            ->assertOk()
            ->assertSee('Article super admin');
    }

    public function test_return_history_is_limited_to_sales_owned_by_non_super_admins(): void
    {
        $owner = $this->posUser();
        $other = $this->posUser();
        $ownSale = $this->sale($owner, 'POS-OWN-HISTORY');
        $otherSale = $this->sale($other, 'POS-OTHER-HISTORY');

        SaleReturn::create([
            'reference' => 'RET-OWN-HISTORY',
            'sale_id' => $ownSale->id,
            'return_date' => now()->toDateString(),
            'total' => 10,
        ]);
        SaleReturn::create([
            'reference' => 'RET-OTHER-HISTORY',
            'sale_id' => $otherSale->id,
            'return_date' => now()->toDateString(),
            'total' => 10,
        ]);

        $this->actingAs($owner)
            ->get(route('pos.returns.index'))
            ->assertOk()
            ->assertSee('RET-OWN-HISTORY')
            ->assertDontSee('RET-OTHER-HISTORY');
    }

    public function test_only_resellable_returned_items_reverse_cost_of_goods_sold(): void
    {
        $user = $this->posUser();
        $sale = $this->sale($user);
        $line = SaleItem::create([
            'sale_id' => $sale->id,
            'item_type' => 'product',
            'item_name' => 'Article avec coût',
            'quantity' => 2,
            'units_per_item' => 1,
            'unit_price' => 50,
            'unit_cost' => 10,
            'subtotal' => 100,
        ]);
        $this->assertSame(
            20.0,
            ProfitHelper::productCogs(now()->toDateString(), now()->toDateString())
        );

        $resellableReturn = SaleReturn::create([
            'reference' => 'RET-RESELLABLE',
            'sale_id' => $sale->id,
            'return_date' => now()->toDateString(),
            'total' => 50,
        ]);
        SaleReturnItem::create([
            'sale_return_id' => $resellableReturn->id,
            'sale_item_id' => $line->id,
            'item_name' => $line->item_name,
            'quantity' => 1,
            'total' => 50,
            'cost_total' => 10,
            'stock_condition' => 'resellable',
        ]);
        $this->assertSame(
            10.0,
            ProfitHelper::productCogs(now()->toDateString(), now()->toDateString())
        );

        $damagedReturn = SaleReturn::create([
            'reference' => 'RET-DAMAGED',
            'sale_id' => $sale->id,
            'return_date' => now()->toDateString(),
            'total' => 50,
        ]);
        SaleReturnItem::create([
            'sale_return_id' => $damagedReturn->id,
            'sale_item_id' => $line->id,
            'item_name' => $line->item_name,
            'quantity' => 1,
            'total' => 50,
            'cost_total' => 10,
            'stock_condition' => 'damaged',
        ]);

        $this->assertSame(
            10.0,
            ProfitHelper::productCogs(now()->toDateString(), now()->toDateString())
        );
    }

    private function posUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo(Permission::firstOrCreate([
            'name' => 'manage pos',
            'guard_name' => 'web',
        ]));

        return $user;
    }

    private function product(string $name, int $stock): Product
    {
        return Product::create([
            'name' => $name,
            'slug' => str()->slug($name),
            'stock_quantity' => $stock,
        ]);
    }

    private function sale(User $user, ?string $reference = null): Sale
    {
        return Sale::create([
            'reference' => $reference ?? 'POS-TEST-'.uniqid(),
            'sale_date' => now()->toDateString(),
            'status' => 'completed',
            'is_pos' => true,
            'total' => 80,
            'amount_paid' => 80,
            'created_by' => $user->id,
        ]);
    }

    private function saleLine(Sale $sale, Product $product, string $name, int $quantity, float $subtotal): SaleItem
    {
        return SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'item_type' => 'product',
            'item_name' => $name,
            'quantity' => $quantity,
            'units_per_item' => 1,
            'unit_price' => $subtotal / $quantity,
            'subtotal' => $subtotal,
        ]);
    }
}
