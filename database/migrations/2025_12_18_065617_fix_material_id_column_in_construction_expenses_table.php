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
        // Check if column exists before adding
        if (!Schema::hasColumn('construction_expenses', 'material_id')) {
            Schema::table('construction_expenses', function (Blueprint $table) {
                $table->foreignId('material_id')->nullable()->after('type')->constrained('construction_materials')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('construction_expenses', 'material_id')) {
            Schema::table('construction_expenses', function (Blueprint $table) {
                $table->dropForeign(['material_id']);
                $table->dropColumn('material_id');
            });
        }
    }
};
