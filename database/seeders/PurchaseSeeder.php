<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::where('code', 'BTQ01')->first();
        $caisse    = PaymentAccount::where('is_default', true)->first();

        $suppliers = Supplier::all()->keyBy('name');
        $products  = Product::all()->keyBy('name');

        $data = [
            [
                'date'     => now()->subDays(90),
                'supplier' => 'Moulin du Sahel',
                'items'    => [['Croissant', 50, 300], ['Pain au chocolat', 40, 350], ['Baguette', 80, 250]],
                'paid'     => true,
            ],
            [
                'date'     => now()->subDays(75),
                'supplier' => 'Laïterie Moderne',
                'items'    => [['Éclair au chocolat', 30, 500], ['Mille-feuille', 20, 1500]],
                'paid'     => true,
            ],
            [
                'date'     => now()->subDays(60),
                'supplier' => "Sucreries d'Abidjan",
                'items'    => [['Macaron', 60, 400], ['Fondant au chocolat', 25, 800]],
                'paid'     => true,
            ],
            [
                'date'     => now()->subDays(45),
                'supplier' => 'Moulin du Sahel',
                'items'    => [['Croissant', 80, 300], ['Brioche', 20, 500], ['Chausson aux pommes', 30, 400]],
                'paid'     => true,
            ],
            [
                'date'     => now()->subDays(30),
                'supplier' => 'Fruits & Saveurs',
                'items'    => [['Tarte aux fraises', 15, 2500], ['Financier', 40, 300]],
                'paid'     => true,
            ],
            [
                'date'     => now()->subDays(20),
                'supplier' => 'Laïterie Moderne',
                'items'    => [['Madeleine', 60, 200], ['Gâteau au chocolat', 5, 5000]],
                'paid'     => true,
            ],
            [
                'date'     => now()->subDays(10),
                'supplier' => 'Œufs CI',
                'items'    => [['Croissant', 100, 300], ['Pain au chocolat', 60, 350]],
                'paid'     => false, // en attente
            ],
            [
                'date'     => now()->subDays(5),
                'supplier' => 'Pack Emballages CI',
                'items'    => [['Financier', 50, 300], ['Macaron', 40, 400]],
                'paid'     => true,
            ],
        ];

        $counter = 1;
        foreach ($data as $row) {
            $supplier  = $suppliers[$row['supplier']] ?? $suppliers->first();
            $reference = 'PUR-' . $row['date']->format('Ymd') . '-' . str_pad($counter++, 4, '0', STR_PAD_LEFT);

            $subtotal = collect($row['items'])->reduce(fn($sum, $i) => $sum + $i[1] * $i[2], 0);

            $purchase = Purchase::create([
                'reference'      => $reference,
                'supplier_id'    => $supplier->id,
                'warehouse_id'   => $warehouse?->id,
                'purchase_date'  => $row['date']->toDateString(),
                'status'         => 'confirmed',
                'payment_status' => $row['paid'] ? 'paid' : 'pending',
                'subtotal'       => $subtotal,
                'total'          => $subtotal,
                'amount_paid'    => $row['paid'] ? $subtotal : 0,
                'created_by'     => 1,
            ]);

            foreach ($row['items'] as [$productName, $qty, $unitPrice]) {
                $product   = $products[$productName] ?? null;
                $lineTotal = $qty * $unitPrice;

                PurchaseItem::create([
                    'purchase_id'    => $purchase->id,
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

                // Mise à jour stock
                if ($product) {
                    $product->increment('stock_quantity', $qty);
                }
            }

            // Paiement
            if ($row['paid'] && $caisse) {
                $ref = 'PAY-' . $row['date']->format('Ymd') . '-' . str_pad(Payment::count() + 1, 5, '0', STR_PAD_LEFT);
                Payment::create([
                    'reference'          => $ref,
                    'payable_type'       => Purchase::class,
                    'payable_id'         => $purchase->id,
                    'payment_account_id' => $caisse->id,
                    'amount'             => $subtotal,
                    'payment_date'       => $row['date']->toDateString(),
                    'payment_method'     => 'cash',
                    'created_by'         => 1,
                ]);
                $caisse->decrement('current_balance', $subtotal);
            }
        }
    }
}
