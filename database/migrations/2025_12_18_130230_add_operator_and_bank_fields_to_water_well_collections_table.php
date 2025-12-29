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
        Schema::table('water_well_collections', function (Blueprint $table) {
            // Drop operator column if it exists (string)
            if (Schema::hasColumn('water_well_collections', 'operator')) {
                $table->dropColumn('operator');
            }
            
            // Add operator_id and bank_account_id
            $table->foreignId('operator_id')->nullable()->after('total_amount')->constrained('users')->onDelete('set null');
            $table->foreignId('bank_account_id')->nullable()->after('operator_id')->constrained('bank_accounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('water_well_collections', function (Blueprint $table) {
            $table->dropForeign(['operator_id']);
            $table->dropForeign(['bank_account_id']);
            $table->dropColumn(['operator_id', 'bank_account_id']);
            
            // Restore operator as string
            $table->string('operator')->nullable()->after('total_amount');
        });
    }
};
