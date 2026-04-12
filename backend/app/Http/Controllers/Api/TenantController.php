<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\CatalogItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TenantController extends Controller
{
    public function show(Request $request)
    {
        $tenant = $request->user()->tenant->load('plan');
        $tenant->days_remaining_in_trial = $tenant->daysRemainingInTrial();
        $tenant->makeVisible('days_remaining_in_trial');
        return response()->json($tenant);
    }

    public function plans()
    {
        $plans = Plan::where('is_active', true)
            ->select('id', 'name', 'display_name', 'catalog_items_limit', 'messages_limit', 'price_cents', 'currency', 'trial_days', 'features')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }

    public function update(Request $request)
    {
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'business_name' => 'sometimes|string|max:255',
            'rubro' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'business_hours' => 'sometimes|array',
            'faqs' => 'sometimes|array',
            'custom_prompt' => 'sometimes|string',
            'payment_transfer_enabled' => 'sometimes|boolean',
            'payment_transfer_cbu' => 'sometimes|string|nullable|max:255',
            'payment_transfer_name' => 'sometimes|string|nullable|max:255',
            'payment_transfer_bank' => 'sometimes|string|nullable|max:255',
            'payment_cash_enabled' => 'sometimes|boolean',
            'payment_cash_note' => 'sometimes|string|nullable|max:255',
        ]);

        $tenant->update($validated);

        return response()->json($tenant);
    }

    public function onboardingStatus(Request $request)
    {
        $tenant = $request->user()->tenant->load('plan');

        // 5 pasos del wizard
        $steps = [
            'business' => !empty($tenant->rubro) && !empty($tenant->description),
            'whatsapp' => $tenant->whatsapp_status === 'connected',
            'catalog' => CatalogItem::where('tenant_id', $tenant->id)->count() > 0,
            'chatbot' => !empty($tenant->custom_prompt),
            'review' => true, // Se marca cuando todo lo anterior está completo
        ];

        // Determinar el paso actual (primero incompleto)
        $currentStep = null;
        foreach ($steps as $key => $completed) {
            if (!$completed) {
                $currentStep = $key;
                break;
            }
        }

        $allCompleted = !in_array(false, $steps, true);

        return response()->json([
            'success' => true,
            'data' => [
                'completed' => $allCompleted,
                'current_step' => $currentStep,
                'onboarding_step' => $tenant->onboarding_step,
                'onboarding_completed' => $tenant->onboarding_completed,
                'steps' => $steps,
            ]
        ]);
    }

    public function updateOnboarding(Request $request)
    {
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'rubro' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'business_hours' => 'sometimes|array',
            'faqs' => 'sometimes|array',
            'custom_prompt' => 'sometimes|string',
            'onboarding_step' => 'sometimes|string|in:business,whatsapp,catalog,chatbot,review',
            'onboarding_completed' => 'sometimes|boolean',
        ]);

        $tenant->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Onboarding actualizado',
            'data' => $tenant->fresh(['plan'])
        ]);
    }
}
