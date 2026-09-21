<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->date('expected_payment_date')->nullable()->after('payment_status');
            $table->timestamp('overdue_notified_at')->nullable()->after('expected_payment_date');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['expected_payment_date', 'overdue_notified_at']);
        });
    }
};
