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
            $table->decimal('quantity', 15, 2)->nullable()->default(1)->after('material_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('construction_expenses', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
