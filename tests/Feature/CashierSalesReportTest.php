<?php

namespace Tests\Feature;

use App\Models\Caisse;
use App\Models\PosSession;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
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
        SaleReturn::create([
            'reference' => 'RET-TEST-0001',
            'sale_id' => $returnedSale->id,
            'return_date' => now()->toDateString(),
            'total' => 100,
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

        $this->actingAs($viewer)
            ->getJson(route('reports.api.cashiers', [
                'date_from' => now()->startOfMonth()->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertJsonPath('rows.0.cashier_name', 'Vendeur CA')
            ->assertJsonPath('rows.0.gross_revenue', 1000)
            ->assertJsonPath('rows.0.returned_total', 100)
            ->assertJsonPath('rows.0.net_revenue', 900)
            ->assertJsonPath('rows.0.profit', 100)
            ->assertJsonPath('rows.0.top_seller', true)
            ->assertJsonPath('rows.1.cashier_name', 'Vendeur bénéfice')
            ->assertJsonPath('rows.1.profit', 600)
            ->assertJsonPath('rows.1.top_profit', true)
            ->assertJsonPath('totals.sales_count', 2)
            ->assertJsonPath('totals.sessions_count', 2)
            ->assertJsonPath('totals.net_revenue', 1600)
            ->assertJsonPath('totals.profit', 700);
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
