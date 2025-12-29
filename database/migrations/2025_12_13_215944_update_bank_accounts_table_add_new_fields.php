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
        Schema::table('bank_accounts', function (Blueprint $table) {
            // Add new columns
            $table->string('branch_name')->nullable()->after('bank_name');
            $table->string('account_name')->nullable()->after('branch_name');
            $table->decimal('opening_balance', 15, 2)->default(0)->after('account_number');
        });
        
        // Copy data from old columns to new columns
        \DB::statement("UPDATE bank_accounts SET account_name = name, opening_balance = COALESCE(balance, 0)");
        
        // Drop old columns
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn(['name', 'balance']);
        });
        
        // Make new columns required
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->string('branch_name')->nullable(false)->change();
            $table->string('account_name')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            // Add back old columns
            $table->string('name')->nullable()->after('id');
            $table->decimal('balance', 15, 2)->default(0)->after('account_number');
        });
        
        // Copy data back
        \DB::statement("UPDATE bank_accounts SET name = account_name, balance = COALESCE(opening_balance, 0)");
        
        // Drop new columns
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn(['account_name', 'branch_name', 'opening_balance']);
        });
        
        // Make old columns required
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });
    }
};
