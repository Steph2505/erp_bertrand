<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Setting;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PosPaginationProductSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(
            ['name' => 'Standard'],
            ['slug' => 'standard', 'is_active' => true]
        );
        $unit = Unit::firstOrCreate(
            ['abbreviation' => 'pcs'],
            ['name' => 'Pièce']
        );
        $warehouseId = Setting::get('default_warehouse_id')
            ?: Warehouse::where('is_active', true)->value('id');

        for ($number = 1; $number <= 45; $number++) {
            $name = 'Article test pagination ' . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
            $stock = 20 + $number;
            $product = Product::firstOrCreate(
                ['name' => $name],
                [
                    'slug'               => Str::slug($name),
                    'category_id'        => $category->id,
                    'unit_id'            => $unit->id,
                    'buying_price'       => 500 + $number,
                    'selling_price'      => 750 + $number,
                    'stock_quantity'     => $stock,
                    'min_stock_quantity' => 5,
                    'is_active'          => true,
                ]
            );

            if ($warehouseId) {
                ProductStock::firstOrCreate(
                    ['product_id' => $product->id, 'warehouse_id' => $warehouseId],
                    ['quantity' => $stock]
                );
            }
        }
    }
}
