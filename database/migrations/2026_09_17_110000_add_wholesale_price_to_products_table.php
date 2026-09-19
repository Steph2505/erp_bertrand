<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // selling_price reste le prix détaillant. wholesale_price est le
            // prix appliqué aux clients du groupe "Grossiste" — nullable :
            // sans valeur, on retombe sur selling_price pour tout le monde.
            $table->decimal('wholesale_price', 12, 2)->nullable()->after('selling_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('wholesale_price');
        });
    }
};
