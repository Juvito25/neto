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
        // Único plan MVP: $19 USD / 1000 mensajes
        $mvpPlan = [
            'name' => 'starter',
            'display_name' => 'Plan Mensual MVP',
            'catalog_items_limit' => 1000,
            'messages_limit' => 10000,
            'price_cents' => 1900,
            'currency' => 'USD',
            'trial_days' => 7,
            'features' => json_encode([
                'Hasta 1000 productos',
                '10000 mensajes por mes',
                'IA de respuesta automática',
                'Ventas automatizadas',
                'Soporte por WhatsApp'
            ]),
            'is_active' => true,
        ];

        // Usamos updateOrCreate sin el ID para evitar el choque de FK si ya existe
        $plan = Plan::updateOrCreate(
            ['name' => 'starter'],
            $mvpPlan
        );

        // Desactivar otros planes si existen
        Plan::where('name', '!=', 'starter')->update(['is_active' => false]);

        // Asegurar que todos los tenants tengan este plan asignado para el lanzamiento
        Tenant::whereNull('plan_id')->update(['plan_id' => $plan->id]);
    }
}
