<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Support\Str;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // Crear planes si no existen
        $plans = [
            [
                'id' => Str::uuid(),
                'name' => 'starter',
                'display_name' => 'Starter',
                'catalog_items_limit' => 500,
                'messages_limit' => 1000,
                'price_cents' => 0,
                'currency' => 'USD',
                'trial_days' => 14,
                'features' => json_encode(['Catálogo básico', '1000 mensajes/mes', 'Soporte por email']),
                'is_active' => true,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'pro',
                'display_name' => 'Pro',
                'catalog_items_limit' => 2000,
                'messages_limit' => 5000,
                'price_cents' => 2900,
                'currency' => 'USD',
                'trial_days' => 14,
                'features' => json_encode(['Hasta 2000 productos', '5000 mensajes/mes', 'Soporte prioritario', 'Estadísticas avanzadas']),
                'is_active' => true,
            ],
            [
                'id' => Str::uuid(),
                'name' => 'enterprise',
                'display_name' => 'Enterprise',
                'catalog_items_limit' => 10000,
                'messages_limit' => 50000,
                'price_cents' => 9900,
                'currency' => 'USD',
                'trial_days' => 30,
                'features' => json_encode(['Hasta 10000 productos', '50000 mensajes/mes', 'Soporte 24/7', 'API dedicada', 'SLA garantizado']),
                'is_active' => true,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['name' => $planData['name']],
                $planData
            );
        }

        // Asignar plan 'pro' a tenants existentes que no tienen plan_id
        $proPlan = Plan::where('name', 'pro')->first();
        if ($proPlan) {
            Tenant::whereNull('plan_id')->update(['plan_id' => $proPlan->id]);
        }
    }
}
