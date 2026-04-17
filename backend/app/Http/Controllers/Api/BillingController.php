<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\PreApproval\PreApprovalClient;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function createSubscription(Request $request)
    {
        $tenant = $request->user()->tenant;
        
        $mercadoPagoToken = config('services.mercadopago.access_token');
        if (empty(trim($mercadoPagoToken))) {
            return response()->json(['message' => 'El dueño no ha configurado sus credenciales de cobro en MercadoPago. MP_ACCESS_TOKEN está vacío.'], 400);
        }

        MercadoPagoConfig::setAccessToken($mercadoPagoToken);

        $client = new PreApprovalClient();
        
        $backUrl = config('app.url') . '/settings?payment=success'; 

        try {
            $preapproval_data = [
                "reason" => "NETO - Plan Pro Mensual",
                "payer_email" => $tenant->email, 
                "auto_recurring" => [
                    "frequency" => 1,
                    "frequency_type" => "months",
                    "transaction_amount" => (float) 20, 
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

        } catch (\Exception $e) {
            Log::error('Mercado Pago Subscription Error:', [
                'message' => $e->getMessage(),
                'payload' => $preapproval_data ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
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
