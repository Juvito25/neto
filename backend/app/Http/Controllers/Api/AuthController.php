<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:tenants',
            'password' => 'required|string|min:8',
            'business_name' => 'required|string|max:255',
            'rubro' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($request) {
            $trialDays = (int) config('app.trial_days', 14);
            $plan = 'pro';

            $tenant = Tenant::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'business_name' => $request->business_name,
                'rubro' => $request->rubro,
                'plan' => $plan,
                'trial_ends_at' => now()->addDays($trialDays),
                'messages_limit' => (int) config('app.plan_pro_limit', 2000),
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

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
                'tenant' => $tenant,
                'token' => $token,
            ], 201);
        });
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $tenant = Tenant::where('email', $request->email)->first();

        if (!$tenant || !Hash::check($request->password, $tenant->password)) {
            return response()->json(['error' => 'Credenciales inválidas'], 401);
        }

        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            $user = User::create([
                'name' => $tenant->name,
                'email' => $tenant->email,
                'password' => $tenant->password,
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
