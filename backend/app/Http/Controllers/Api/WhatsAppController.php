<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppController extends Controller
{
    public function status(Request $request)
    {
        $tenant = $request->user()->tenant;
        
        $instance = WhatsappInstance::where('tenant_id', $tenant->id)->first();

        if (!$instance) {
            return response()->json([
                'status' => 'not_configured',
                'message' => 'No hay instancia de WhatsApp configurada',
            ]);
        }

        $evolutionUrl = config('services.evolution.url');
        $evolutionKey = config('services.evolution.key');

        try {
            $response = Http::withHeaders([
                'apikey' => $evolutionKey,
            ])->get("{$evolutionUrl}/instance/connectionState/{$instance->instance_name}");

            if ($response->successful()) {
                $state = $response->json('data.state');
                $statusMap = [
                    'open' => 'connected',
                    'close' => 'disconnected',
                ];
                
                $currentStatus = $statusMap[$state] ?? $instance->status;
                
                if ($currentStatus !== $instance->status) {
                    $instance->update(['status' => $currentStatus]);
                    $tenant->update(['whatsapp_status' => $currentStatus]);
                }

                return response()->json([
                    'status' => $currentStatus,
                    'instance_name' => $instance->instance_name,
                    'connected_at' => $instance->connected_at,
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('WhatsAppController: Failed to get status', [
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'status' => $instance->status,
            'instance_name' => $instance->instance_name,
        ]);
    }

    public function connect(Request $request)
    {
        $tenant = $request->user()->tenant;
        
        $instance = WhatsappInstance::where('tenant_id', $tenant->id)->first();

        if (!$instance) {
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
                return response()->json(['error' => 'Error al crear instancia: ' . $e->getMessage()], 500);
            }
        }

        return response()->json([
            'status' => $instance->status,
            'instance_name' => $instance->instance_name,
        ]);
    }

    public function disconnect(Request $request)
    {
        $tenant = $request->user()->tenant;
        
        $instance = WhatsappInstance::where('tenant_id', $tenant->id)->first();

        if ($instance) {
            $evolutionUrl = config('services.evolution.url');
            $evolutionKey = config('services.evolution.key');

            try {
                Http::withHeaders([
                    'apikey' => $evolutionKey,
                ])->delete("{$evolutionUrl}/instance/delete/{$instance->instance_name}");
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('WhatsAppController: Failed to delete instance', [
                    'error' => $e->getMessage(),
                ]);
            }

            $instance->delete();
            $tenant->update(['whatsapp_status' => 'disconnected']);
        }

        return response()->json(['message' => 'WhatsApp desconectado']);
    }

    public function qr(Request $request)
    {
        $tenant = $request->user()->tenant;
        
        $instance = WhatsappInstance::where('tenant_id', $tenant->id)->first();

        if (!$instance) {
            return response()->json(['error' => 'No hay instancia'], 404);
        }

        if ($instance->status === 'connected') {
            return response()->json(['status' => 'connected', 'message' => 'Ya conectado']);
        }

        $evolutionUrl = config('services.evolution.url');
        $evolutionKey = config('services.evolution.key');

        try {
            $response = Http::withHeaders([
                'apikey' => $evolutionKey,
            ])->get("{$evolutionUrl}/instance/connect/{$instance->instance_name}");

            if ($response->successful()) {
                $data = $response->json('data');
                
                $instance->update([
                    'qr_code' => $data['qrcode']['code'] ?? null,
                ]);

                return response()->json([
                    'qr_code' => $instance->qr_code,
                    'status' => 'connecting',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener QR: ' . $e->getMessage()], 500);
        }

        return response()->json(['status' => $instance->status]);
    }
}
