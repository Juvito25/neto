<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\PreApproval\PreApprovalClient;
use Throwable;

class BillingController extends Controller
{
    /**
     * Obtiene la cotización del dólar blue desde dolarapi.com
     */
    private function getDolarBlueCotizacion(): ?float
    {
        $cacheKey = 'billing:dolar_blue_venta';

        try {
            $response = Http::timeout(8)->acceptJson()->get('https://dolarapi.com/v1/dolares/blue');
            if ($response->successful()) {
                $data = $response->json();
                $venta = isset($data['venta']) ? (float) $data['venta'] : null;
                if ($venta && $venta > 0) {
                    // Guardamos última cotización válida por 6 horas.
                    Cache::put($cacheKey, $venta, now()->addHours(6));
                    return $venta;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error fetching dolar blue: ' . $e->getMessage());
        }

        $cached = Cache::get($cacheKey);
        if (!empty($cached) && (float) $cached > 0) {
            Log::warning('Usando cotización dólar blue en caché por fallo del endpoint', [
                'venta' => (float) $cached,
            ]);
            return (float) $cached;
        }

        return null;
    }

    public function createSubscription(Request $request)
    {
        $tenant = $request->user()->tenant;
        
        $mercadoPagoToken = config('services.mercadopago.access_token');
        if (empty(trim($mercadoPagoToken))) {
            return response()->json(['message' => 'El dueño no ha configurado sus credenciales de cobro en MercadoPago. MP_ACCESS_TOKEN está vacío.'], 400);
        }

        MercadoPagoConfig::setAccessToken($mercadoPagoToken);

        $client = new PreApprovalClient();

        // Si ya existe una suscripción en MP para este tenant, evitamos crear duplicados.
        if (!empty($tenant->mp_subscription_id)) {
            try {
                $existing = $client->get($tenant->mp_subscription_id);
                if ($existing) {
                    $existingStatus = $existing->status ?? null;
                    $existingInitPoint = $existing->init_point ?? ($existing->sandbox_init_point ?? null);

                    if ($existingStatus === 'authorized') {
                        return response()->json([
                            'success' => true,
                            'already_active' => true,
                            'message' => 'Tu suscripción ya está activa.',
                            'id' => $existing->id ?? $tenant->mp_subscription_id,
                        ]);
                    }

                    if (in_array($existingStatus, ['pending', 'authorized'], true) && !empty($existingInitPoint)) {
                        return response()->json([
                            'success' => true,
                            'already_exists' => true,
                            'init_point' => $existingInitPoint,
                            'id' => $existing->id ?? $tenant->mp_subscription_id,
                        ]);
                    }
                }
            } catch (Throwable $e) {
                // Si la suscripción previa no existe en MP (o no se puede leer), continuamos creando una nueva.
                Log::warning('No se pudo consultar suscripción MP existente', [
                    'tenant_id' => $tenant->id,
                    'mp_subscription_id' => $tenant->mp_subscription_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        $backUrl = config('app.url') . '/settings?payment=success';

        // Obtener el plan del tenant para calcular el precio
        $plan = $tenant->plan;
        if (!$plan || $plan->price_cents <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'El plan seleccionado no tiene un precio válido para suscripción.',
            ], 422);
        }

        $precioUSD = $plan->price_cents / 100;
        $dolarBlue = $this->getDolarBlueCotizacion();

        if (!$dolarBlue) {
            $fallbackCotizacion = (float) config('services.mercadopago.dolar_blue_fallback', 0);
            if ($fallbackCotizacion > 0) {
                $dolarBlue = $fallbackCotizacion;
                Log::warning('Usando cotización fallback de configuración', [
                    'fallback' => $fallbackCotizacion,
                ]);
            }
        }

        if (!$dolarBlue) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener la cotización del dólar blue. Intentá nuevamente en unos minutos.',
            ], 503);
        }

        $transactionAmount = round($precioUSD * $dolarBlue);
        if ($transactionAmount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo calcular el monto de la suscripción.',
            ], 500);
        }

        Log::info("Precio calculado: {$precioUSD} USD x \$ {$dolarBlue} = \$ {$transactionAmount} ARS", [
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'plan_price_cents' => $plan->price_cents,
        ]);

        $payerEmail = $request->user()->email ?: $tenant->email;
        if (empty($payerEmail) || !filter_var($payerEmail, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'No hay un email válido para iniciar la suscripción.',
            ], 422);
        }

        try {
            $preapproval_data = [
                "reason" => "NETO - Plan Pro Mensual",
                "payer_email" => $payerEmail,
                "auto_recurring" => [
                    "frequency" => 1,
                    "frequency_type" => "months",
                    "transaction_amount" => (float) $transactionAmount, 
                    "currency_id" => "ARS" 
                ],
                "back_url" => $backUrl,
                "status" => "pending"
            ];

            $preapproval = $client->create($preapproval_data);

            Log::info('Mercado Pago Full Response:', (array) $preapproval);

            $tenant->update(['mp_subscription_id' => $preapproval->id]);

            $initPoint = $preapproval->init_point ?? null;
            if (!$initPoint && isset($preapproval->sandbox_init_point)) {
                $initPoint = $preapproval->sandbox_init_point;
            }

            return response()->json([
                'success' => true,
                'init_point' => $initPoint,
                'id' => $preapproval->id
            ]);

        } catch (Throwable $e) {
            Log::error('Mercado Pago Subscription Error:', [
                'message' => $e->getMessage(),
                'tenant_id' => $tenant->id,
                'mp_subscription_id' => $tenant->mp_subscription_id,
                'payload' => $preapproval_data ?? null
            ]);

            // Caso común: usuario ya tiene una suscripción creada para el mismo payer_email.
            // Lo devolvemos como error de negocio (409) en vez de 500.
            $message = $e->getMessage();
            if (str_contains(strtolower($message), 'already') || str_contains(strtolower($message), 'exist')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una suscripción para este usuario. Si ya pagaste, refrescá la página o revisá Configuración.',
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => 'No se pudo iniciar la suscripción en este momento. Intentá nuevamente en unos minutos.'
            ], 500);
        }
    }

    public function webhook(Request $request)
    {
        Log::info('MP Webhook received', $request->all());
        
        $type = $request->input('type');
        $dataId = $request->input('data.id');

        if ($type === 'subscription_preapproval') {
            $mercadoPagoToken = config('services.mercadopago.access_token');
            MercadoPagoConfig::setAccessToken($mercadoPagoToken);
            
            $client = new PreApprovalClient();
            $sub = $client->get($dataId);
            
            if ($sub) {
                $tenant = Tenant::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                            ->where('mp_subscription_id', $sub->id)
                            ->first();

                if ($tenant) {
                    if ($sub->status === 'authorized') {
                        // Calcular subscription_ends_at considerando días de trial restantes
                        $trialDaysRemaining = (int) $tenant->trial_remaining_days;
                        $subscriptionDays = 30 + $trialDaysRemaining;
                        
                        $tenant->update([
                            'subscription_status' => 'active',
                            'subscribed_at' => now(),
                            'subscription_ends_at' => now()->addDays($subscriptionDays),
                            'trial_remaining_days' => 0,
                            'messages_used' => 0,
                        ]);
                        
                        Log::info('Suscripción activada', [
                            'tenant_id' => $tenant->id,
                            'trial_days_used' => 7 - $trialDaysRemaining,
                            'subscription_ends_at' => now()->addDays($subscriptionDays),
                        ]);
                    } elseif (in_array($sub->status, ['cancelled', 'paused'])) {
                        $tenant->update([
                            'subscription_status' => 'cancelled'
                        ]);
                    }
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    public function status(Request $request)
    {
        $tenant = $request->user()->tenant;
        return response()->json([
            'tenant' => [
                'subscription_status' => $tenant->subscription_status,
                'trial_ends_at' => $tenant->trial_ends_at,
                'trial_remaining_days' => $tenant->trial_remaining_days,
                'subscription_ends_at' => $tenant->subscription_ends_at,
                'days_remaining' => $tenant->subscription_status === 'trial' 
                    ? $tenant->daysRemainingInTrial() 
                    : $tenant->daysRemainingInSubscription(),
            ],
        ]);
    }
}
