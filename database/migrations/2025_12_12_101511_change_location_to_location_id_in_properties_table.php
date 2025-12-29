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
        Schema::table('properties', function (Blueprint $table) {
            // Drop the old location column
            $table->dropColumn('location');
            // Add location_id column
            $table->foreignId('location_id')->nullable()->after('property_type_id')->constrained('locations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Drop location_id
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
            // Restore location column
            $table->string('location')->after('name');
        });
    }
};
