<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            // Quantité avant/après ce mouvement, au périmètre concerné (stock du
            // magasin si warehouse_id est renseigné, sinon stock global du
            // produit) — rend chaque mouvement auditable isolément, sans avoir
            // à rejouer tout l'historique pour vérifier un chiffre.
            $table->integer('quantity_before')->nullable()->after('quantity');
            $table->integer('quantity_after')->nullable()->after('quantity_before');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn(['quantity_before', 'quantity_after']);
        });
    }
};
