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
        Schema::create('water_well_collections', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('buckets_sold');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_amount', 15, 2);
            $table->string('operator');
            $table->string('deposit_id')->nullable();
            $table->date('deposit_date')->nullable();
            $table->boolean('is_deposited')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('water_well_collections');
    }
};
