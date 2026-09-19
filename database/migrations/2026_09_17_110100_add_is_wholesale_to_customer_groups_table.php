<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_groups', function (Blueprint $table) {
            // Détermine si les clients de ce groupe se voient appliquer le
            // prix de gros (Product::wholesale_price) plutôt que le prix
            // détaillant — plus robuste qu'un test sur le nom du groupe,
            // qui reste renommable librement par l'admin.
            $table->boolean('is_wholesale')->default(false)->after('discount');
        });

        // Marque le groupe "Grossiste" déjà seedé, si présent.
        DB::table('customer_groups')->where('name', 'Grossiste')->update(['is_wholesale' => true]);
    }

    public function down(): void
    {
        Schema::table('customer_groups', function (Blueprint $table) {
            $table->dropColumn('is_wholesale');
        });
    }
};
