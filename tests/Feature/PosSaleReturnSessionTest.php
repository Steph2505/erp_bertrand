<?php

namespace Tests\Feature;

use App\Helpers\FormatHelper;
use App\Models\Caisse;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PosSaleReturnSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_refund_during_an_open_session_reduces_expected_drawer_amount(): void
    {
        $user = $this->userWithPermissions(['manage pos']);
        $account = $this->account();
        $session = $this->makePosSession($user, 100, 50);
        $sale = $this->sale($user, 'SALE-OPEN-SESSION');
        $line = $this->saleItem($sale);
        $this->payment($sale, 50, 'cash');

        $this->actingAs($user)->post(route('pos.returns.store'), $this->returnData($sale, $line, $account))
            ->assertRedirect(route('pos.returns.index'))
            ->assertSessionHas('success');

        $return = SaleReturn::firstOrFail();
        $this->assertSame($session->id, $return->pos_session_id);
        $this->assertSame(50.0, $session->fresh()->cash_refunds);
        $this->assertSame(50.0, $session->fresh()->cash_sales);
        $this->assertSame(50.0, $session->fresh()->expected_closing);
        $this->assertSame(100.0, (float) $account->fresh()->current_balance);
    }

    public function test_cash_refund_after_a_session_closed_is_outside_session_and_requires_a_manager(): void
    {
        $cashier = $this->userWithPermissions(['manage pos']);
        $account = $this->account();
        $closedSession = $this->makePosSession($cashier, 100, 50, now()->subDays(2));
        $closedSession->update(['closed_at' => now()->subDays(1), 'closing_balance' => 150]);
        $sale = $this->sale($cashier, 'SALE-CLOSED-SESSION');
        $line = $this->saleItem($sale);
        $payload = $this->returnData($sale, $line, $account);

        $this->actingAs($cashier)
            ->from(route('pos.returns.create', ['sale_id' => $sale->id]))
            ->post(route('pos.returns.store'), $payload)
            ->assertRedirect(route('pos.returns.create', ['sale_id' => $sale->id]))
            ->assertSessionHasErrors('payment_method');

        $this->assertDatabaseCount('sale_returns', 0);

        $manager = $this->userWithPermissions(['manage pos'], 'Admin');
        $managedSale = $this->sale($manager, 'SALE-MANAGER-CLOSED-SESSION');
        $managerLine = $this->saleItem($managedSale);

        $this->actingAs($manager)
            ->post(route('pos.returns.store'), $this->returnData($managedSale, $managerLine, $account))
            ->assertRedirect(route('pos.returns.index'))
            ->assertSessionHas('success');

        $return = SaleReturn::firstOrFail();
        $this->assertNull($return->pos_session_id);
        $this->assertSame(150.0, (float) $closedSession->fresh()->closing_balance);
        $this->assertSame(100.0, (float) $account->fresh()->current_balance);
        $this->assertDatabaseHas('payments', [
            'payable_type' => SaleReturn::class,
            'payable_id' => $return->id,
            'amount' => 50,
        ]);
    }

    public function test_expected_cash_uses_cash_payments_and_stays_fixed_after_closure(): void
    {
        $user = $this->userWithPermissions(['manage pos']);
        $session = $this->makePosSession($user, 50, 100);
        $sale = $this->sale($user, 'SALE-CASH-ONLY');
        $this->saleItem($sale);
        $this->payment($sale, 50, 'cash');

        $this->payment($sale, 40, 'card');

        $this->assertSame(50.0, $session->fresh()->cash_sales);
        $this->assertSame(150.0, $session->fresh()->expected_closing);
        $session->update([
            'closing_balance' => 150,
            'expected_closing_balance' => 150,
            'closed_at' => now(),
        ]);

        $latePayment = Payment::create([
            'reference' => 'PAY-LATE-'.uniqid(),
            'payable_type' => Sale::class,
            'payable_id' => $sale->id,
            'amount' => 20,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);
        $latePayment->forceFill(['created_at' => now()->addSecond()])->save();

        $this->assertSame(150.0, $session->fresh()->expected_closing);
        $this->assertSame(50.0, $session->fresh()->cash_sales);
    }

    public function test_account_journal_and_cash_flow_include_out_of_session_refunds(): void
    {
        $user = $this->userWithPermissions(['manage pos', 'manage accounts', 'view reports'], 'Super Admin');
        $account = $this->account();
        $sale = $this->sale($user, 'SALE-LEDGER');
        $line = $this->saleItem($sale);
        $this->actingAs($user)->post(route('pos.returns.store'), $this->returnData($sale, $line, $account))
            ->assertRedirect(route('pos.returns.index'));
        $this->assertDatabaseHas('payments', [
            'payable_type' => SaleReturn::class,
            'amount' => 50,
        ]);
        $this->getJson(route('payment-accounts.api.show', $account))
            ->assertOk()
            ->assertJsonPath('data.0.label', 'Remboursement client RET-'.now()->format('Ymd').'-0001')
            ->assertJsonPath('data.0.debit', 50)
            ->assertJsonPath('total_debits', 50);

        $this->get(route('accounts.cash-flow', ['year' => now()->year]))
            ->assertOk()
            ->assertSee('Remboursements')
            ->assertSee(FormatHelper::money(50));

        $this->get(route('accounts.balance-sheet', ['date' => now()->toDateString()]))
            ->assertOk();
        $this->get(route('accounts.trial-balance', ['year' => now()->year]))
            ->assertOk()
            ->assertSee('Remboursements');

        $this->getJson(route('reports.api.sale-payments', [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.is_refund', true)
            ->assertJsonPath('data.0.amount', '− '.FormatHelper::money(50))
            ->assertJsonPath('sum_amount', -50);
    }

    private function userWithPermissions(array $permissions, ?string $role = null): User
    {
        $user = User::factory()->create(['is_active' => true]);
        if ($role) {
            $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));
        }
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]));
        }

        return $user;
    }

    private function account(): PaymentAccount
    {
        return PaymentAccount::create([
            'name' => 'Caisse retour',
            'type' => 'cash',
            'current_balance' => 150,
        ]);
    }

    private function makePosSession(User $user, float $totalSales, float $openingBalance, $openedAt = null): PosSession
    {
        $caisse = Caisse::create(['name' => 'Caisse '.uniqid()]);

        return PosSession::create([
            'caisse_id' => $caisse->id,
            'user_id' => $user->id,
            'opening_balance' => $openingBalance,
            'total_sales' => $totalSales,
            'opened_at' => $openedAt ?? now()->subHour(),
        ]);
    }

    private function sale(User $user, string $reference): Sale
    {
        return Sale::create([
            'reference' => $reference,
            'pos_session_id' => PosSession::where('user_id', $user->id)->whereNull('closed_at')->value('id'),
            'sale_date' => now()->toDateString(),
            'status' => 'completed',
            'is_pos' => true,
            'total' => 50,
            'amount_paid' => 50,
            'created_by' => $user->id,
        ]);
    }

    private function saleItem(Sale $sale): SaleItem
    {
        $product = Product::create([
            'name' => 'Article test',
            'slug' => 'article-'.uniqid(),
            'stock_quantity' => 0,
        ]);

        return SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'item_type' => 'product',
            'item_name' => 'Article test',
            'quantity' => 1,
            'units_per_item' => 1,
            'unit_price' => 50,
            'subtotal' => 50,
        ]);
    }

    private function payment(Sale $sale, float $amount, string $method): Payment
    {
        return Payment::create([
            'reference' => 'PAY-'.uniqid(),
            'payable_type' => Sale::class,
            'payable_id' => $sale->id,
            'amount' => $amount,
            'payment_date' => now()->toDateString(),
            'payment_method' => $method,
            'created_by' => $sale->created_by,
        ]);
    }

    private function returnData(Sale $sale, SaleItem $line, PaymentAccount $account): array
    {
        return [
            'sale_id' => $sale->id,
            'return_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'payment_account_id' => $account->id,
            'items' => [
                $line->id => ['quantity' => 1, 'stock_condition' => 'damaged'],
            ],
        ];
    }
}
