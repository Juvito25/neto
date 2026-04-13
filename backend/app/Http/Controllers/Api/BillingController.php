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
        
        $backUrl = config('app.url') . '/settings'; 

        try {
            $preapproval = $client->create([
                "reason" => "NETO - Plan Mensual",
                "auto_recurring" => [
                    "frequency" => 1,
                    "frequency_type" => "months",
                    "transaction_amount" => 19000, 
                    "currency_id" => "ARS" 
                ],
                "back_url" => $backUrl,
                "status" => "pending"
            ]);

            $tenant->update(['mp_subscription_id' => $preapproval->id]);

            return response()->json(['init_point' => $preapproval->init_point]);
        } catch (\Exception $e) {
            Log::error('Billing error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error creando suscripcion: ' . $e->getMessage()], 500);
        }
    }

    public function webhook(Request $request)
    {
        Log::info('MP Webhook received', $request->all());
        
        $type = $request->input('type');
        $dataId = $request->input('data.id');

        if ($type === 'subscription_preapproval') {
            MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));
            $client = new PreApprovalClient();
            $sub = $client->get($dataId);
            
            if ($sub) {
                // Remove global scope if webhook is unauthenticated
                $tenant = Tenant::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                            ->where('mp_subscription_id', $sub->id)
                            ->first();

                if ($tenant) {
                    if ($sub->status === 'authorized') {
                        $tenant->update([
                            'subscription_status' => 'active',
                            'subscribed_at' => now(),
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
            'subscription_status' => $tenant->subscription_status,
            'trial_ends_at' => $tenant->trial_ends_at,
            'days_remaining' => $tenant->daysRemainingInTrial(),
        ]);
    }
}
