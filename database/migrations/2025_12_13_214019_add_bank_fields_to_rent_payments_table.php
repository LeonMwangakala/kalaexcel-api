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
        Schema::table('rent_payments', function (Blueprint $table) {
            $table->foreignId('bank_account_id')->nullable()->after('contract_id')->constrained('bank_accounts')->onDelete('set null');
            $table->string('bank_receipt')->nullable()->after('bank_account_id');
        });
        
        // For PostgreSQL, we need to alter the enum type
        \DB::statement("ALTER TABLE rent_payments DROP CONSTRAINT IF EXISTS rent_payments_status_check");
        \DB::statement("ALTER TABLE rent_payments ALTER COLUMN status TYPE VARCHAR(255)");
        \DB::statement("ALTER TABLE rent_payments ADD CONSTRAINT rent_payments_status_check CHECK (status IN ('paid', 'pending', 'overdue', 'partial'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rent_payments', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropColumn(['bank_account_id', 'bank_receipt']);
        });
        
        // Revert status enum
        \DB::statement("ALTER TABLE rent_payments DROP CONSTRAINT IF EXISTS rent_payments_status_check");
        \DB::statement("ALTER TABLE rent_payments ALTER COLUMN status TYPE VARCHAR(255)");
        \DB::statement("ALTER TABLE rent_payments ADD CONSTRAINT rent_payments_status_check CHECK (status IN ('paid', 'pending', 'overdue'))");
    }
};
