<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('business_name');
            $table->string('rubro')->nullable();
            $table->text('description')->nullable();
            $table->json('business_hours')->nullable();
            $table->json('faqs')->nullable();
            $table->text('custom_prompt')->nullable();
            $table->enum('plan', ['starter', 'pro', 'business'])->default('pro');
            $table->timestamp('trial_ends_at');
            $table->integer('messages_used')->default(0);
            $table->integer('messages_limit')->default(2000);
            $table->string('whatsapp_instance_id')->nullable();
            $table->enum('whatsapp_status', ['disconnected', 'connecting', 'connected'])->default('disconnected');
            $table->string('stripe_customer_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
