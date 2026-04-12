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
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('payment_transfer_enabled')->default(false);
            $table->string('payment_transfer_cbu')->nullable();
            $table->string('payment_transfer_name')->nullable();
            $table->string('payment_transfer_bank')->nullable();
            $table->boolean('payment_cash_enabled')->default(false);
            $table->string('payment_cash_note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'payment_transfer_enabled',
                'payment_transfer_cbu',
                'payment_transfer_name',
                'payment_transfer_bank',
                'payment_cash_enabled',
                'payment_cash_note'
            ]);
        });
    }
};
