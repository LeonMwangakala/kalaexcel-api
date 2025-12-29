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
        Schema::create('water_supply_customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('location');
            $table->string('meter_number')->unique();
            $table->decimal('starting_reading', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->date('date_registered');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('water_supply_customers');
    }
};
