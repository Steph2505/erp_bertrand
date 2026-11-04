<?php

namespace Tests\Feature;

use App\Models\Caisse;
use App\Models\PosSession;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CashierSalesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_ranks_cashiers_by_net_revenue_and_profit_and_deducts_returns(): void
    {
        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->givePermissionTo(Permission::firstOrCreate([
            'name' => 'view reports',
            'guard_name' => 'web',
        ]));

        $caisse = Caisse::create(['name' => 'Caisse test']);
        $cashierByRevenue = User::factory()->create(['name' => 'Vendeur CA', 'is_active' => true]);
        $cashierByProfit = User::factory()->create(['name' => 'Vendeur bénéfice', 'is_active' => true]);
        $sessionByRevenue = $this->createSession($cashierByRevenue, $caisse);
        $sessionByProfit = $this->createSession($cashierByProfit, $caisse);

        $returnedSale = $this->createSale($sessionByRevenue, 'CA-1000', 1000, 800);
        $this->createSale($sessionByProfit, 'BEN-700', 700, 100);
        $return = SaleReturn::create([
            'reference' => 'RET-TEST-0001',
            'sale_id' => $returnedSale->id,
            'return_date' => now()->toDateString(),
            'total' => 100,
            'created_by' => $cashierByProfit->id,
        ]);
        SaleReturnItem::create([
            'sale_return_id' => $return->id,
            'sale_item_id' => $returnedSale->items()->first()->id,
            'item_name' => 'Article test',
            'quantity' => 1,
            'total' => 50,
            'cost_total' => 40,
            'stock_condition' => 'resellable',
        ]);
        $extraItem = SaleItem::create([
            'sale_id' => $returnedSale->id,
            'item_type' => 'product',
            'item_name' => 'Article test complémentaire',
            'quantity' => 1,
            'units_per_item' => 1,
            'unit_price' => 0,
            'unit_cost' => 0,
            'subtotal' => 0,
        ]);
        SaleReturnItem::create([
            'sale_return_id' => $return->id,
            'sale_item_id' => $extraItem->id,
            'item_name' => $extraItem->item_name,
            'quantity' => 1,
            'total' => 50,
            'cost_total' => 40,
            'stock_condition' => 'resellable',
        ]);
        $this->assertDatabaseHas('sales', [
            'id' => $returnedSale->id,
            'is_pos' => 1,
            'pos_session_id' => $sessionByRevenue->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('reports.cashiers'))
            ->assertOk()
            ->assertViewIs('pages.reports.cashiers');

        $response = $this->actingAs($viewer)
            ->getJson(route('reports.api.cashiers', [
                'date_from' => now()->startOfMonth()->toDateString(),
                'date_to' => now()->toDateString(),
            ]));
        $response->assertOk()
            ->assertJsonPath('rows.0.cashier_name', 'Vendeur CA')
            ->assertJsonPath('rows.0.gross_revenue', 1000)
            ->assertJsonPath('rows.0.returned_total', 0)
            ->assertJsonPath('rows.0.net_revenue', 1000)
            ->assertJsonPath('rows.0.profit', 200)
            ->assertJsonPath('rows.0.top_seller', true)
            ->assertJsonPath('rows.1.cashier_name', 'Vendeur bénéfice')
            ->assertJsonPath('rows.1.returned_total', 100)
            ->assertJsonPath('rows.1.profit', 580)
            ->assertJsonPath('rows.1.top_profit', true)
            ->assertJsonPath('totals.sales_count', 2)
            ->assertJsonPath('totals.sessions_count', 2)
            ->assertJsonPath('totals.net_revenue', 1600)
            ->assertJsonPath('totals.profit', 780);
    }

    public function test_profit_loss_report_dates_revenue_and_recoverable_cost_to_the_return_day(): void
    {
        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->givePermissionTo(Permission::firstOrCreate([
            'name' => 'view reports',
            'guard_name' => 'web',
        ]));
        $cashier = User::factory()->create(['is_active' => true]);
        $session = $this->createSession($cashier, Caisse::create(['name' => 'Caisse P&L']));
        $sale = $this->createSale($session, 'PL-RETURN', 100, 40);
        $saleItem = $sale->items()->first();
        $return = SaleReturn::create([
            'reference' => 'RET-PL-RETURN',
            'sale_id' => $sale->id,
            'return_date' => now()->toDateString(),
            'total' => 25,
            'created_by' => $cashier->id,
        ]);
        SaleReturnItem::create([
            'sale_return_id' => $return->id,
            'sale_item_id' => $saleItem->id,
            'item_name' => $saleItem->item_name,
            'quantity' => 1,
            'total' => 25,
            'cost_total' => 10,
            'stock_condition' => 'resellable',
        ]);

        $this->actingAs($viewer)
            ->getJson(route('reports.api.profit-loss', [
                'date_from' => now()->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertJsonPath('revenue', 75)
            ->assertJsonPath('cogs', 30)
            ->assertJsonPath('gross_profit', 45);
    }

    public function test_return_is_reported_on_its_date_and_under_its_processor_not_the_original_seller(): void
    {
        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->givePermissionTo(Permission::firstOrCreate([
            'name' => 'view reports',
            'guard_name' => 'web',
        ]));
        $seller = User::factory()->create(['name' => 'Vendeur précédent', 'is_active' => true]);
        $processor = User::factory()->create(['name' => 'Caissier retour', 'is_active' => true]);
        $sale = $this->createSale(
            $this->createSession($seller, Caisse::create(['name' => 'Caisse historique'])),
            'SALE-PREVIOUS-PERIOD',
            120,
            60
        );
        $sale->update(['sale_date' => now()->subMonth()->toDateString()]);
        $saleItem = $sale->items()->first();
        $return = SaleReturn::create([
            'reference' => 'RET-CURRENT-PERIOD',
            'sale_id' => $sale->id,
            'return_date' => now()->toDateString(),
            'total' => 30,
            'created_by' => $processor->id,
        ]);
        SaleReturnItem::create([
            'sale_return_id' => $return->id,
            'sale_item_id' => $saleItem->id,
            'item_name' => $saleItem->item_name,
            'quantity' => 1,
            'total' => 30,
            'cost_total' => 15,
            'stock_condition' => 'resellable',
        ]);

        $this->actingAs($viewer)
            ->getJson(route('reports.api.cashiers', [
                'date_from' => now()->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'rows')
            ->assertJsonPath('rows.0.cashier_name', 'Caissier retour')
            ->assertJsonPath('rows.0.sales_count', 0)
            ->assertJsonPath('rows.0.returned_total', 30)
            ->assertJsonPath('rows.0.net_revenue', -30)
            ->assertJsonPath('rows.0.cost', -15)
            ->assertJsonPath('rows.0.profit', -15);
    }

    private function createSession(User $cashier, Caisse $caisse): PosSession
    {
        return PosSession::create([
            'caisse_id' => $caisse->id,
            'user_id' => $cashier->id,
            'opening_balance' => 0,
            'total_sales' => 0,
            'opened_at' => now(),
        ]);
    }

    private function createSale(PosSession $session, string $reference, float $total, float $cost): Sale
    {
        $sale = Sale::create([
            'reference' => $reference,
            'pos_session_id' => $session->id,
            'sale_date' => now()->toDateString(),
            'status' => 'completed',
            'payment_status' => 'paid',
            'subtotal' => $total,
            'total' => $total,
            'amount_paid' => $total,
            'is_pos' => true,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'item_type' => 'product',
            'item_name' => 'Article test',
            'quantity' => 1,
            'units_per_item' => 1,
            'unit_price' => $total,
            'unit_cost' => $cost,
            'subtotal' => $total,
        ]);

        return $sale;
    }
}
