<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function show(Request $request)
    {
        $tenant = $request->user()->tenant;
        return response()->json($tenant);
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
        ]);

        $tenant->update($validated);

        return response()->json($tenant);
    }

    public function onboardingStatus(Request $request)
    {
        $tenant = $request->user()->tenant;

        $steps = [
            'business' => !empty($tenant->description) || !empty($tenant->business_hours),
            'products' => $tenant->products()->count() > 0,
            'whatsapp' => $tenant->whatsapp_status === 'connected',
        ];

        $completed = array_filter($steps) === $steps;
        $currentStep = array_keys($steps)[array_search(false, array_values($steps))] ?? null;

        return response()->json([
            'completed' => $completed,
            'current_step' => $currentStep,
            'steps' => $steps,
        ]);
    }
}
