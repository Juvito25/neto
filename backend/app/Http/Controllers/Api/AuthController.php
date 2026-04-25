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
use Illuminate\Support\Facades\Log;
use Throwable;

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
            'password_confirmation.min' => 'La confirmación debe tener al menos 8 caracteres',
            'business_name.required' => 'El nombre del negocio es obligatorio',
            'business_name.min' => 'El nombre del negocio debe tener al menos 2 caracteres',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                // Obtener plan por defecto (starter)
                $defaultPlan = Plan::where('name', 'starter')->first();
                
                if (!$defaultPlan) {
                    Log::warning('AuthController: Starter plan not found in database, using defaults.');
                }

                $trialDays = $defaultPlan ? $defaultPlan->trial_days : 14;

                $tenant = Tenant::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'business_name' => $validated['business_name'],
                    'rubro' => $validated['rubro'] ?? null,
                    'plan_id' => $defaultPlan?->id,
                    'onboarding_step' => 'business',
                    'onboarding_completed' => false,
                    'trial_ends_at' => now()->addDays($trialDays),
                    'trial_remaining_days' => $trialDays,
                    'subscription_status' => 'trial',
                ]);

                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'tenant_id' => $tenant->id,
                ]);

                // Crear instancia de WhatsApp
                $instanceName = 'neto_' . $tenant->id . '_' . time();
                $evolutionUrl = config('services.evolution.url');
                $evolutionKey = config('services.evolution.key');

                if ($evolutionUrl && $evolutionKey) {
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
                                'url' => 'https://app.netoia.cloud/api/webhooks/whatsapp',
                                'webhookByEvents' => true,
                                'webhookEvents' => ['messages.upsert', 'connection.update', 'qrcode'],
                            ]);
                        } else {
                            Log::error('AuthController: Evolution API returned error', [
                                'status' => $response->status(),
                                'body' => $response->body(),
                            ]);
                        }
                    } catch (Throwable $e) {
                        Log::error('AuthController: Failed to create WhatsApp instance', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                } else {
                    Log::warning('AuthController: Evolution API URL or Key missing in config');
                }

                $token = $user->createToken('auth-token')->plainTextToken;

                return response()->json([
                    'user' => $user,
                    'tenant' => $tenant->load('plan'),
                    'token' => $token,
                ], 201);
            });
        } catch (Throwable $e) {
            Log::error('AuthController: Register failed', [
                'email' => $validated['email'] ?? null,
                'error' => $e->getMessage(),
            ]);

            $errorMessage = strtolower($e->getMessage());
            if (str_contains($errorMessage, 'users_email_unique') || str_contains($errorMessage, 'duplicate')) {
                return response()->json([
                    'message' => 'Este email ya está registrado. Probá iniciar sesión o usar otro email.',
                ], 422);
            }

            return response()->json([
                'message' => 'No se pudo crear la cuenta en este momento. Si el email ya existe, iniciá sesión.',
            ], 500);
        }
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
