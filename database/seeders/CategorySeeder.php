<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Patisseries',
            'Pains & Baguettes',
            'Boissons',
            'Confiseries',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name], ['slug' => Str::slug($name), 'is_active' => true]);
        }
    }
}
