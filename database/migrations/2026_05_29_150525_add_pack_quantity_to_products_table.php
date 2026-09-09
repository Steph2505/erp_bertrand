<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Nombre d'unités contenues dans un pack de ce produit (ex: boîte de 6 = 6)
            $table->unsignedInteger('pack_quantity')->default(1)->after('can_be_packed');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('pack_quantity');
        });
    }
};
