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
        Schema::create('water_supply_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('water_supply_customers')->onDelete('cascade');
            $table->date('reading_date');
            $table->decimal('meter_reading', 10, 2);
            $table->decimal('previous_reading', 10, 2);
            $table->decimal('units_consumed', 10, 2);
            $table->decimal('amount_due', 15, 2);
            $table->enum('payment_status', ['paid', 'pending', 'overdue'])->default('pending');
            $table->date('payment_date')->nullable();
            $table->string('month'); // Format: YYYY-MM
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('water_supply_readings');
    }
};
