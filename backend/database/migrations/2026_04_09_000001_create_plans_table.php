<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique(); // starter, pro, enterprise
            $table->string('display_name');
            $table->integer('catalog_items_limit')->default(500);
            $table->integer('messages_limit')->default(10000);
            $table->integer('price_cents')->default(0);
            $table->string('currency')->default('USD');
            $table->integer('trial_days')->default(7);
            $table->text('features')->nullable(); // JSON con características
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insertar planes por defecto
        $starterId = Str::uuid();
        $proId = Str::uuid();
        $enterpriseId = Str::uuid();

        DB::table('plans')->insert([
            [
                'id' => Str::uuid(),
                'name' => 'starter',
                'display_name' => 'Starter',
                'catalog_items_limit' => 500,
                'messages_limit' => 10000,
                'price_cents' => 0,
                'currency' => 'USD',
                'trial_days' => 7,
                'features' => json_encode(['Catálogo básico', '10000 mensajes/mes', 'Soporte por email']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'pro',
                'display_name' => 'Pro',
                'catalog_items_limit' => 2000,
                'messages_limit' => 50000,
                'price_cents' => 2900,
                'currency' => 'USD',
                'trial_days' => 7,
                'features' => json_encode(['Hasta 2000 productos', '50000 mensajes/mes', 'Soporte prioritario', 'Estadísticas avanzadas']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'enterprise',
                'display_name' => 'Enterprise',
                'catalog_items_limit' => 10000,
                'messages_limit' => 500000,
                'price_cents' => 9900,
                'currency' => 'USD',
                'trial_days' => 7,
                'features' => json_encode(['Hasta 10000 productos', '500000 mensajes/mes', 'Soporte 24/7', 'API dedicada', 'SLA garantizado']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
