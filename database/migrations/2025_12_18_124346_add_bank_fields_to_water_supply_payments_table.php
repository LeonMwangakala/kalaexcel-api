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
        Schema::table('water_supply_payments', function (Blueprint $table) {
            $table->foreignId('bank_account_id')->nullable()->after('payment_method')->constrained('bank_accounts')->onDelete('set null');
            $table->string('bank_receipt')->nullable()->after('bank_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('water_supply_payments', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropColumn(['bank_account_id', 'bank_receipt']);
        });
    }
};
