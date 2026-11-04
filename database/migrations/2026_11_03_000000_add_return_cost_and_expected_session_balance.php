<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_return_items', function (Blueprint $table) {
            $table->decimal('cost_total', 12, 2)->nullable()->after('total');
        });

        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->decimal('expected_closing_balance', 12, 2)->nullable()->after('closing_balance');
        });
    }

    public function down(): void
    {
        Schema::table('sale_return_items', function (Blueprint $table) {
            $table->dropColumn('cost_total');
        });

        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->dropColumn('expected_closing_balance');
        });
    }
};
