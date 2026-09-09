<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $piece   = Unit::where('abbreviation', 'pcs')->first();
        $kg      = Unit::where('abbreviation', 'kg')->first();
        $litre   = Unit::where('abbreviation', 'L')->first();
        $plateau = Unit::where('abbreviation', 'plat')->first();

        $vien = Category::where('name', 'Viennoiseries')->first();
        $gat  = Category::where('name', 'Gâteaux & Entremets')->first();
        $pain = Category::where('name', 'Pains & Baguettes')->first();
        $bois = Category::where('name', 'Boissons')->first();
        $conf = Category::where('name', 'Confiseries')->first();
        $tart = Category::where('name', 'Tartes & Quiches')->first();

        $products = [
            // Viennoiseries
            ['name' => 'Croissant',          'cat' => $vien, 'unit' => $piece,  'buy' => 300,  'sell' => 500,   'stock' => 240, 'min' => 30,  'packable' => true],
            ['name' => 'Pain au chocolat',   'cat' => $vien, 'unit' => $piece,  'buy' => 350,  'sell' => 600,   'stock' => 180, 'min' => 20,  'packable' => true],
            ['name' => 'Chausson aux pommes','cat' => $vien, 'unit' => $piece,  'buy' => 400,  'sell' => 650,   'stock' => 120, 'min' => 15,  'packable' => true],
            ['name' => 'Brioche',            'cat' => $vien, 'unit' => $piece,  'buy' => 500,  'sell' => 800,   'stock' => 80,  'min' => 10,  'packable' => true],

            // Gâteaux & Entremets
            ['name' => 'Éclair au chocolat', 'cat' => $gat,  'unit' => $piece,  'buy' => 500,  'sell' => 800,   'stock' => 96,  'min' => 10,  'packable' => true],
            ['name' => 'Macaron',            'cat' => $gat,  'unit' => $piece,  'buy' => 400,  'sell' => 700,   'stock' => 120, 'min' => 20,  'packable' => true],
            ['name' => 'Tarte aux fraises',  'cat' => $tart, 'unit' => $piece,  'buy' => 2500, 'sell' => 4000,  'stock' => 24,  'min' => 3,   'packable' => true],
            ['name' => 'Gâteau au chocolat', 'cat' => $gat,  'unit' => $piece,  'buy' => 5000, 'sell' => 8000,  'stock' => 12,  'min' => 2,   'packable' => false],
            ['name' => 'Mille-feuille',      'cat' => $gat,  'unit' => $piece,  'buy' => 1500, 'sell' => 2500,  'stock' => 36,  'min' => 5,   'packable' => true],
            ['name' => 'Fondant au chocolat','cat' => $gat,  'unit' => $piece,  'buy' => 800,  'sell' => 1200,  'stock' => 60,  'min' => 8,   'packable' => true],

            // Pains & Baguettes
            ['name' => 'Baguette',           'cat' => $pain, 'unit' => $piece,  'buy' => 250,  'sell' => 400,   'stock' => 80,  'min' => 20,  'packable' => false],
            ['name' => 'Pain de mie',        'cat' => $pain, 'unit' => $piece,  'buy' => 600,  'sell' => 1000,  'stock' => 40,  'min' => 10,  'packable' => false],
            ['name' => 'Pain complet',       'cat' => $pain, 'unit' => $piece,  'buy' => 700,  'sell' => 1100,  'stock' => 30,  'min' => 5,   'packable' => false],

            // Boissons
            ["name" => "Jus d'orange",       'cat' => $bois, 'unit' => $litre,  'buy' => 800,  'sell' => 1200,  'stock' => 48,  'min' => 10,  'packable' => true],
            ['name' => 'Eau minérale 50cl',  'cat' => $bois, 'unit' => $piece,  'buy' => 250,  'sell' => 400,   'stock' => 120, 'min' => 24,  'packable' => false],

            // Confiseries
            ['name' => 'Financier',          'cat' => $conf, 'unit' => $piece,  'buy' => 300,  'sell' => 500,   'stock' => 90,  'min' => 12,  'packable' => true],
            ['name' => 'Madeleine',          'cat' => $conf, 'unit' => $piece,  'buy' => 200,  'sell' => 350,   'stock' => 150, 'min' => 20,  'packable' => true],
        ];

        foreach ($products as $p) {
            Product::firstOrCreate(['name' => $p['name']], [
                'slug'             => Str::slug($p['name']),
                'category_id'      => $p['cat']?->id,
                'unit_id'          => $p['unit']?->id,
                'buying_price'     => $p['buy'],
                'selling_price'    => $p['sell'],
                'stock_quantity'   => $p['stock'],
                'min_stock_quantity' => $p['min'],
                'can_be_packed'    => $p['packable'],
                'is_active'        => true,
            ]);
        }
    }
}
