<?php

namespace Database\Seeders;

use App\Models\Pack;
use App\Models\PackItem;
use App\Models\PackPrice;
use App\Models\Product;
use Illuminate\Database\Seeder;

class PackSeeder extends Seeder
{
    public function run(): void
    {
        $croissant    = Product::where('name', 'Croissant')->first();
        $painChoco    = Product::where('name', 'Pain au chocolat')->first();
        $eclair       = Product::where('name', 'Éclair au chocolat')->first();
        $macaron      = Product::where('name', 'Macaron')->first();
        $jus          = Product::where("name", "Jus d'orange")->first();
        $financier    = Product::where('name', 'Financier')->first();
        $madeleine    = Product::where('name', 'Madeleine')->first();
        $chausson     = Product::where('name', 'Chausson aux pommes')->first();

        $packs = [
            [
                'name' => 'Boîte 6 Croissants',
                'items' => [[$croissant, 6]],
                'buying' => 1800, 'selling' => 2700,
            ],
            [
                'name' => 'Boîte 6 Pains au chocolat',
                'items' => [[$painChoco, 6]],
                'buying' => 2100, 'selling' => 3200,
            ],
            [
                'name' => 'Carton 24 Croissants',
                'items' => [[$croissant, 24]],
                'buying' => 7200, 'selling' => 10000,
            ],
            [
                'name' => 'Coffret Découverte',
                'items' => [[$croissant, 3], [$painChoco, 2], [$eclair, 1]],
                'buying' => 2600, 'selling' => 4000,
            ],
            [
                'name' => 'Pack Petit-Déjeuner',
                'items' => [[$croissant, 2], [$painChoco, 1], [$jus, 1]],
                'buying' => 2150, 'selling' => 3200,
            ],
            [
                'name' => 'Assortiment Macarons x12',
                'items' => [[$macaron, 12]],
                'buying' => 4800, 'selling' => 7500,
            ],
            [
                'name' => 'Plateau Gourmand',
                'items' => [[$financier, 4], [$madeleine, 4], [$macaron, 4]],
                'buying' => 3600, 'selling' => 5500,
            ],
            [
                'name' => 'Boîte Viennoiseries Mixte',
                'items' => [[$croissant, 3], [$chausson, 2], [$painChoco, 1]],
                'buying' => 2600, 'selling' => 3900,
            ],
        ];

        foreach ($packs as $data) {
            $pack = Pack::firstOrCreate(['name' => $data['name']], ['is_active' => true]);

            if ($pack->items()->count() === 0) {
                foreach ($data['items'] as [$product, $qty]) {
                    if ($product) {
                        PackItem::create(['pack_id' => $pack->id, 'product_id' => $product->id, 'quantity' => $qty]);
                    }
                }
                PackPrice::create([
                    'pack_id'       => $pack->id,
                    'price_group_id' => null,
                    'buying_price'  => $data['buying'],
                    'selling_price' => $data['selling'],
                ]);
            }
        }
    }
}
