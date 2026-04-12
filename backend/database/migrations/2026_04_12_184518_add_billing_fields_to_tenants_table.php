<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('subscription_status')->default('trial');
            $table->string('mp_subscription_id')->nullable();
            $table->string('mp_customer_id')->nullable();
            $table->timestamp('subscribed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_status',
                'mp_subscription_id',
                'mp_customer_id',
                'subscribed_at',
            ]);
        });
    }
};
