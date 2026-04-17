<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappInstance;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|string|email|max:255|unique:tenants,email',
            'password' => 'required|string|min:8|confirmed',
            'business_name' => 'required|string|min:2|max:255',
            'password_confirmation' => 'required|string|min:8',
        ], [
            'name.required' => 'El nombre es obligatorio',
            'name.min' => 'El nombre debe tener al menos 2 caracteres',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'Ingresa un email válido (ej: tu@email.com)',
            'email.unique' => 'Este email ya está registrado',
            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'password_confirmation.required' => 'Debes confirmar la contraseña',
            'business_name.required' => 'El nombre del negocio es obligatorio',
            'business_name.min' => 'El nombre del negocio debe tener al menos 2 caracteres',
        ]);

        return DB::transaction(function () use ($request) {
            // Obtener plan por defecto (starter)
            $defaultPlan = Plan::where('name', 'starter')->first();
            $trialDays = $defaultPlan ? $defaultPlan->trial_days : 14;

            $tenant = Tenant::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'business_name' => $request->business_name,
                'rubro' => $request->rubro,
                'plan_id' => $defaultPlan?->id,
                'onboarding_step' => 'business',
                'onboarding_completed' => false,
                'trial_ends_at' => now()->addDays($trialDays),
                'trial_remaining_days' => $trialDays,
                'subscription_status' => 'trial',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'tenant_id' => $tenant->id,
            ]);

            // Crear instancia de WhatsApp
            $instanceName = 'neto_' . $tenant->id . '_' . time();
            $evolutionUrl = config('services.evolution.url');
            $evolutionKey = config('services.evolution.key');

            try {
                $response = Http::withHeaders([
                    'apikey' => $evolutionKey,
                    'Content-Type' => 'application/json',
                ])->post("{$evolutionUrl}/instance/create", [
                    'instanceName' => $instanceName,
                    'qrCode' => true,
                ]);

                if ($response->successful()) {
                    $instance = WhatsappInstance::create([
                        'tenant_id' => $tenant->id,
                        'instance_name' => $instanceName,
                        'status' => 'connecting',
                    ]);

                    $tenant->update([
                        'whatsapp_instance_id' => $instance->id,
                    ]);

                    Http::withHeaders([
                        'apikey' => $evolutionKey,
                        'Content-Type' => 'application/json',
                    ])->post("{$evolutionUrl}/webhook/set/{$instanceName}", [
                        'url' => config('app.url') . '/api/webhooks/whatsapp',
                        'webhookByEvents' => true,
                        'webhookEvents' => ['messages.upsert', 'connection.update', 'qrcode'],
                    ]);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('AuthController: Failed to create WhatsApp instance', [
                    'error' => $e->getMessage(),
                ]);
            }

            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'tenant' => $tenant->load('plan'),
                'token' => $token,
            ], 201);
        });
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'El email es obligatorio',
            'email.email' => 'Ingresa un email válido',
            'password.required' => 'La contraseña es obligatoria',
        ]);

        $tenant = Tenant::where('email', $request->email)->first();

        if (!$tenant || !Hash::check($request->password, $tenant->password)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            $user = User::create([
                'name' => $tenant->name,
                'email' => $tenant->email,
                'password' => $tenant->password,
                'tenant_id' => $tenant->id,
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'tenant' => $tenant,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
