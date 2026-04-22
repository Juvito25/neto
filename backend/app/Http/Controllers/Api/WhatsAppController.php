<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
                $data = $response->json();
                $state = $data['instance']['state'] ?? $data['state'] ?? null;
                
                $statusMap = [
                    'open' => 'connected',
                    'close' => 'disconnected',
                    'connecting' => 'connecting',
                    'ready' => 'connecting',
                ];
                
                $currentStatus = $statusMap[$state] ?? $instance->status;
                
                if ($currentStatus !== $instance->status) {
                    $instance->update([
                        'status' => $currentStatus,
                        'connected_at' => $currentStatus === 'connected' ? now() : null,
                    ]);
                    $tenant->update(['whatsapp_status' => $currentStatus]);
                }

                return response()->json([
                    'status' => $currentStatus,
                    'api_state' => $state,
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
                // Evolution API v2 format
                $response = Http::withHeaders([
                    'apikey' => $evolutionKey,
                    'Content-Type' => 'application/json',
                ])->post("{$evolutionUrl}/instance/create", [
                    'instanceName' => $instanceName,
                    'qrcode' => true,
                    'integration' => 'WHATSAPP-BAILEYS',
                ]);

                $responseData = $response->json();
                
                if ($response->successful() || isset($responseData['instance'])) {
                    $instance = WhatsappInstance::create([
                        'tenant_id' => $tenant->id,
                        'instance_name' => $instanceName,
                        'status' => 'connecting',
                    ]);

                    // Configure webhook automatically via nginx (Evolution can't reach PHP-FPM directly)
                    Http::withHeaders([
                        'apikey' => $evolutionKey,
                        'Content-Type' => 'application/json',
                    ])->post("{$evolutionUrl}/webhook/set/{$instanceName}", [
                        'webhook' => [
                            'enabled' => true,
                            'url' => 'http://neto_nginx:8888/api/webhooks/whatsapp',
                            'webhookByEvents' => false,
                            'webhookBase64' => false,
                            'events' => ['MESSAGES_UPSERT', 'CONNECTION_UPDATE'],
                        ],
                    ]);
                } else {
                    return response()->json([
                        'message' => 'Error al crear instancia',
                        'details' => $responseData
                    ], 500);
                }
            } catch (\Exception $e) {
                return response()->json(['message' => 'Error al crear instancia: ' . $e->getMessage()], 500);
            }
        }

        return response()->json([
            'status' => $instance?->status ?? 'unknown',
            'instance_name' => $instance?->instance_name,
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
        
        $instance = WhatsappInstance::where('tenant_id', $tenant->id)->orderBy('created_at', 'desc')->first();

        if (!$instance) {
            return response()->json(['message' => 'No hay instancia. Primero conectá WhatsApp desde Settings.'], 404);
        }

        $evolutionUrl = config('services.evolution.url');
        $evolutionKey = config('services.evolution.key');

        // Check connection state
        try {
            $stateResponse = Http::withHeaders([
                'apikey' => $evolutionKey,
            ])->get("{$evolutionUrl}/instance/connectionState/{$instance->instance_name}");
            
            if ($stateResponse->successful()) {
                $stateData = $stateResponse->json();
                $state = $stateData['instance']['state'] ?? null;
                
                if ($state === 'open') {
                    $instance->update(['status' => 'connected', 'connected_at' => now()]);
                    $tenant->update(['whatsapp_status' => 'connected']);
                    
                    return response()->json([
                        'status' => 'connected',
                        'message' => 'WhatsApp ya está conectado',
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('WhatsAppController: Failed to check connection state', ['error' => $e->getMessage()]);
        }

        if ($instance->status === 'connected') {
            return response()->json(['status' => 'connected', 'message' => 'Ya conectado']);
        }

        // Get QR code - Evolution API v2
        try {
            $response = Http::withHeaders([
                'apikey' => $evolutionKey,
            ])->get("{$evolutionUrl}/instance/connect/{$instance->instance_name}");

            if ($response->successful()) {
                $data = $response->json();
                
                // Get QR code - Evolution API v2 response parsing
                $qrCode = $data['base64'] ?? 
                          $data['code'] ?? 
                          $data['qrcode']['base64'] ?? 
                          $data['qrcode']['code'] ?? 
                          $data['data']['base64'] ?? 
                          $data['data']['qrcode']['base64'] ?? 
                          null;
                
                if ($qrCode) {
                    // Add data:image prefix if needed
                    if (!str_starts_with($qrCode, 'data:')) {
                        $qrCode = 'data:image/png;base64,' . $qrCode;
                    }

                    $instance->update([
                        'qr_code' => $qrCode,
                        'status' => 'connecting',
                    ]);

                    return response()->json([
                        'qr_code' => $qrCode,
                        'status' => 'connecting',
                    ]);
                }

                Log::info('WhatsAppController: QR response success but no QR found', ['data' => $data]);

                return response()->json([
                    'status' => 'connecting',
                    'message' => 'Esperando escaneo...',
                    'debug_info' => 'No QR in response, check logs',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp QR Error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error al obtener QR: ' . $e->getMessage()], 500);
        }

        return response()->json(['status' => $instance->status]);
    }

    public function checkMessages(Request $request)
    {
        $tenant = $request->user()->tenant;
        
        $instance = WhatsappInstance::where('tenant_id', $tenant->id)->first();

        if (!$instance || $instance->status !== 'connected') {
            return response()->json(['new_messages' => 0]);
        }

        $evolutionUrl = config('services.evolution.url');
        $evolutionKey = config('services.evolution.key');

        try {
            $response = Http::withHeaders([
                'apikey' => $evolutionKey,
            ])->get("{$evolutionUrl}/chat/list/{$instance->instance_name}?count=1");

            if ($response->successful()) {
                $data = $response->json();
                $chats = $data['chats'] ?? [];
                
                return response()->json([
                    'new_messages' => count($chats),
                    'status' => $instance->status,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('WhatsAppController: Failed to check messages', ['error' => $e->getMessage()]);
        }

        return response()->json(['new_messages' => 0, 'status' => $instance->status]);
    }
}
