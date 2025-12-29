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
        Schema::table('toilet_collections', function (Blueprint $table) {
            $table->dropColumn('cashier');
        });

        Schema::table('toilet_collections', function (Blueprint $table) {
            $table->foreignId('cashier_id')->nullable()->after('amount_collected')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('toilet_collections', function (Blueprint $table) {
            $table->dropForeign(['cashier_id']);
            $table->dropColumn('cashier_id');
        });

        Schema::table('toilet_collections', function (Blueprint $table) {
            $table->string('cashier')->after('amount_collected');
        });
    }
};
