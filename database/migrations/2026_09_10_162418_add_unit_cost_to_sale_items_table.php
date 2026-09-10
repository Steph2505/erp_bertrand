<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 12, 2)->nullable()->after('unit_price');
        });

        // Backfill des lignes existantes avec le coût actuel (produit ou pack) —
        // meilleure estimation possible puisque le coût historique réel n'était pas conservé.
        DB::statement("
            UPDATE sale_items
            SET unit_cost = (SELECT buying_price FROM products WHERE products.id = sale_items.product_id)
            WHERE sale_items.item_type = 'product' AND sale_items.product_id IS NOT NULL
        ");

        DB::statement("
            UPDATE sale_items
            SET unit_cost = (
                SELECT buying_price FROM pack_prices
                WHERE pack_prices.pack_id = sale_items.pack_id AND pack_prices.price_group_id IS NULL
                LIMIT 1
            )
            WHERE sale_items.item_type = 'pack' AND sale_items.pack_id IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
