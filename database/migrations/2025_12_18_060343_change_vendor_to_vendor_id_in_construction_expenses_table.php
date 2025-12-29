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
        Schema::table('construction_expenses', function (Blueprint $table) {
            // Add vendor_id column
            $table->foreignId('vendor_id')->nullable()->after('date')->constrained('vendors')->onDelete('set null');
        });
        
        // Drop the old vendor string column
        Schema::table('construction_expenses', function (Blueprint $table) {
            $table->dropColumn('vendor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('construction_expenses', function (Blueprint $table) {
            // Add back vendor string column
            $table->string('vendor')->after('date');
        });
        
        // Drop vendor_id foreign key and column
        Schema::table('construction_expenses', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropColumn('vendor_id');
        });
    }
};
