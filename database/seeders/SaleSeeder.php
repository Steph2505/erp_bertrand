<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::where('code', 'BTQ01')->first();
        $caisse    = PaymentAccount::where('is_default', true)->first();

        $customers = Customer::all()->keyBy('name');
        $products  = Product::all()->keyBy('name');

        $data = [
            // Ventes confirmées des 3 derniers mois
            ['date' => now()->subDays(88), 'customer' => 'Hôtel Ivoire',       'items' => [['Croissant', 30, 500], ['Pain au chocolat', 20, 600]], 'paid' => true, 'mode' => 'bank_transfer'],
            ['date' => now()->subDays(80), 'customer' => 'Café de la Paix',    'items' => [['Croissant', 15, 500], ['Baguette', 10, 400], ['Éclair au chocolat', 8, 800]], 'paid' => true, 'mode' => 'cash'],
            ['date' => now()->subDays(72), 'customer' => 'Supermarché Éden',   'items' => [['Macaron', 24, 700], ['Madeleine', 30, 350], ['Financier', 20, 500]], 'paid' => true, 'mode' => 'mobile_money'],
            ['date' => now()->subDays(65), 'customer' => 'Restaurant Maquis',  'items' => [['Pain de mie', 10, 1000], ['Croissant', 20, 500], ['Brioche', 8, 800]], 'paid' => true, 'mode' => 'cash'],
            ['date' => now()->subDays(58), 'customer' => 'Ama Kouassi',        'items' => [['Tarte aux fraises', 2, 4000], ['Macaron', 6, 700]], 'paid' => true, 'mode' => 'cash'],
            ['date' => now()->subDays(50), 'customer' => 'Hôtel Ivoire',       'items' => [['Gâteau au chocolat', 2, 8000], ['Mille-feuille', 10, 2500]], 'paid' => true, 'mode' => 'bank_transfer'],
            ['date' => now()->subDays(42), 'customer' => 'Boulangerie Amour',  'items' => [['Croissant', 50, 500], ['Pain au chocolat', 40, 600], ['Chausson aux pommes', 20, 650]], 'paid' => true, 'mode' => 'bank_transfer'],
            ['date' => now()->subDays(35), 'customer' => 'Kofi Mensah',        'items' => [['Fondant au chocolat', 4, 1200], ['Éclair au chocolat', 3, 800]], 'paid' => true, 'mode' => 'mobile_money'],
            ['date' => now()->subDays(28), 'customer' => 'Café de la Paix',    'items' => [['Pain de mie', 8, 1000], ['Pain complet', 5, 1100], ['Baguette', 20, 400]], 'paid' => true, 'mode' => 'cash'],
            ['date' => now()->subDays(22), 'customer' => 'Supermarché Éden',   'items' => [['Macaron', 36, 700], ['Financier', 24, 500], ['Madeleine', 48, 350]], 'paid' => true, 'mode' => 'mobile_money'],
            ['date' => now()->subDays(18), 'customer' => 'Marie Dupont',       'items' => [['Tarte aux fraises', 1, 4000], ['Brioche', 2, 800], ["Jus d'orange", 2, 1200]], 'paid' => true, 'mode' => 'cash'],
            ['date' => now()->subDays(14), 'customer' => 'Restaurant Maquis',  'items' => [['Croissant', 25, 500], ['Baguette', 15, 400]], 'paid' => true, 'mode' => 'cash'],
            ['date' => now()->subDays(10), 'customer' => 'Hôtel Ivoire',       'items' => [['Gâteau au chocolat', 3, 8000], ['Tarte aux fraises', 5, 4000]], 'paid' => true, 'mode' => 'bank_transfer'],
            ['date' => now()->subDays(7),  'customer' => 'Jean-Marc Traoré',   'items' => [['Macaron', 8, 700], ['Éclair au chocolat', 4, 800]], 'paid' => true, 'mode' => 'mobile_money'],
            ['date' => now()->subDays(5),  'customer' => 'Fatou Diallo',       'items' => [['Croissant', 6, 500], ['Chausson aux pommes', 3, 650]], 'paid' => true, 'mode' => 'cash'],
            ['date' => now()->subDays(3),  'customer' => 'Boulangerie Amour',  'items' => [['Pain au chocolat', 30, 600], ['Brioche', 10, 800]], 'paid' => true, 'mode' => 'bank_transfer'],
            ['date' => now()->subDays(2),  'customer' => 'Café de la Paix',    'items' => [['Mille-feuille', 5, 2500], ['Fondant au chocolat', 6, 1200]], 'paid' => false, 'mode' => 'cash'],
            ['date' => now()->subDays(1),  'customer' => null,                 'items' => [['Croissant', 5, 500], ['Pain au chocolat', 3, 600], ["Jus d'orange", 1, 1200]], 'paid' => true, 'mode' => 'cash'],
        ];

        $counter = 1;
        foreach ($data as $row) {
            $customer  = $row['customer'] ? ($customers[$row['customer']] ?? null) : null;
            $reference = 'SAL-' . $row['date']->format('Ymd') . '-' . str_pad($counter++, 4, '0', STR_PAD_LEFT);
            $subtotal  = collect($row['items'])->reduce(fn($sum, $i) => $sum + $i[1] * $i[2], 0);

            $sale = Sale::create([
                'reference'      => $reference,
                'customer_id'    => $customer?->id,
                'warehouse_id'   => $warehouse?->id,
                'sale_date'      => $row['date']->toDateString(),
                'status'         => 'confirmed',
                'payment_status' => $row['paid'] ? 'paid' : 'pending',
                'is_pos'         => false,
                'subtotal'       => $subtotal,
                'total'          => $subtotal,
                'amount_paid'    => $row['paid'] ? $subtotal : 0,
                'created_by'     => 1,
            ]);

            foreach ($row['items'] as [$productName, $qty, $unitPrice]) {
                $product   = $products[$productName] ?? null;
                $lineTotal = $qty * $unitPrice;

                SaleItem::create([
                    'sale_id'        => $sale->id,
                    'product_id'     => $product?->id,
                    'item_type'      => 'product',
                    'item_name'      => $productName,
                    'quantity'       => $qty,
                    'units_per_item' => 1,
                    'unit_price'     => $unitPrice,
                    'discount'       => 0,
                    'tax_rate'       => 0,
                    'subtotal'       => $lineTotal,
                ]);

                if ($product) {
                    $product->decrement('stock_quantity', $qty);
                }
            }

            if ($row['paid'] && $caisse) {
                // Sélectionner le compte selon le mode
                $account = match($row['mode']) {
                    'mobile_money'  => PaymentAccount::where('type', 'mobile_money')->first() ?? $caisse,
                    'bank_transfer' => PaymentAccount::where('type', 'bank')->first() ?? $caisse,
                    default         => $caisse,
                };
                $ref = 'PAY-' . $row['date']->format('Ymd') . '-' . str_pad(Payment::count() + 1, 5, '0', STR_PAD_LEFT);
                Payment::create([
                    'reference'          => $ref,
                    'payable_type'       => Sale::class,
                    'payable_id'         => $sale->id,
                    'payment_account_id' => $account->id,
                    'amount'             => $subtotal,
                    'payment_date'       => $row['date']->toDateString(),
                    'payment_method'     => $row['mode'],
                    'created_by'         => 1,
                ]);
                $account->increment('current_balance', $subtotal);
            }
        }
    }
}
