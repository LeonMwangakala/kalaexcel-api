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
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('contract_number')->nullable()->after('id');
        });

        // Generate contract numbers for existing contracts
        $contracts = \App\Models\Contract::orderBy('id')->get();
        foreach ($contracts as $index => $contract) {
            $contractNumber = 'CON' . str_pad($contract->id, 6, '0', STR_PAD_LEFT);
            $contract->update(['contract_number' => $contractNumber]);
        }

        // Now make it unique and not null
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('contract_number')->unique()->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('contract_number');
        });
    }
};
