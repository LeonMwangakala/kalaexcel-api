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
        Schema::create('toilet_collections', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('total_users');
            $table->decimal('amount_collected', 15, 2);
            $table->string('cashier');
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
        Schema::dropIfExists('toilet_collections');
    }
};
