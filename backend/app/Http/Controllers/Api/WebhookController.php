<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingMessageJob;
use App\Models\Tenant;
use App\Models\WhatsappInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function whatsapp(Request $request)
    {
        // Debug: always return this first to see if endpoint is hit
        file_put_contents('/tmp/webhook.log', date('Y-m-d H:i:s') . ' - endpoint hit\n', FILE_APPEND);
        
        // Log completo del payload para debugging
        Log::info('=== WHATSAPP WEBHOOK RECEIVED ===', [
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'headers' => [
                'X-Evolution-Instance' => $request->header('X-Evolution-Instance'),
                'Content-Type' => $request->header('Content-Type'),
                'apikey' => substr((string)$request->header('apikey'), 0, 5) . '...',
            ],
            'query' => $request->query->all(),
            // 'body' => $request->all(), // Evitar logear el body completo si es muy grande
        ]);

        // Security: Validate API Key
        $evolutionKey = config('services.evolution.key');
        if ($request->header('apikey') !== $evolutionKey) {
            Log::warning('Webhook Security: Invalid apikey', [
                'provided' => substr((string)$request->header('apikey'), 0, 5) . '...',
                'ip' => $request->ip()
            ]);
            // Return 200 to not reveal that we are blocking the request to potential attackers
            return response()->json(['status' => 'ok']);
        }

        $rawPayload = $request->getContent();
        file_put_contents('/tmp/webhook.log', date('Y-m-d H:i:s') . ' - ' . $rawPayload . "\n", FILE_APPEND);
        
        $payload = $request->all();
        $event = $payload['event'] ?? null;

        Log::info('Webhook event parsed', [
            'event' => $event,
        ]);

        if (!$event) {
            Log::warning('Webhook: No event in payload');
            return response()->json(['status' => 'ok']);
        }

        // DEBUG: Log the payload structure
        Log::debug('Webhook payload', [
            'keys' => array_keys($payload),
            'data_keys' => isset($payload['data']) ? array_keys($payload['data']) : 'no data',
        ]);

        $instanceName = $request->header('X-Evolution-Instance') 
                      ?? $payload['instance'] 
                      ?? $payload['instanceId'] 
                      ?? null;
        
        Log::debug('Looking for instance', ['instanceName' => $instanceName]);
        
        if (!$instanceName) {
            Log::warning('Webhook: No instance name found');
            return response()->json(['status' => 'ok']);
        }

        $instance = WhatsappInstance::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('instance_name', $instanceName)
            ->first();

        if (!$instance) {
            return response()->json(['status' => 'ok']);
        }

        $tenant = $instance->tenant;
        $tenantId = $tenant->id; // Get numeric ID, not UUID

        $payload = $request->all();

        if (isset($payload['event']) && $payload['event'] === 'qrcode') {
            $instance->update([
                'qr_code' => $payload['data']['qrCode']['code'] ?? null,
                'status' => 'connecting',
            ]);
            return response()->json(['status' => 'QR updated']);
        }

        if (isset($payload['event']) && $payload['event'] === 'connection.update') {
            if (isset($payload['data']['state'])) {
                $statusMap = [
                    'open' => 'connected',
                    'close' => 'disconnected',
                ];
                $status = $statusMap[$payload['data']['state']] ?? 'connecting';
                
                $instance->update([
                    'status' => $status,
                    'connected_at' => $status === 'connected' ? now() : null,
                ]);

                $tenant->update(['whatsapp_status' => $status]);
            }
            return response()->json(['status' => 'Connection updated']);
        }

        if (isset($payload['event']) && $payload['event'] === 'messages.upsert') {
            // Evolution API v2 format: data can be a single message or array of messages
            $data = $payload['data'] ?? [];
            
            // Check if data has 'key' (single message format) or 'messages' (batch format)
            if (isset($data['key']) && isset($data['key']['remoteJid'])) {
                $messages = [$data]; // Single message
            } elseif (isset($data['messages']) && is_array($data['messages'])) {
                $messages = $data['messages']; // Batch format
            } else {
                Log::warning('Webhook: Unknown message format', ['data' => $data]);
                return response()->json(['status' => 'unknown_format']);
            }
            
            // Filter out non-message entries
            $messages = array_filter($messages, function($msg) {
                return isset($msg['key']) && isset($msg['key']['remoteJid']);
            });
            
            if (empty($messages)) {
                Log::warning('Webhook: No valid messages after filter');
                return response()->json(['status' => 'no_messages']);
            }
            
            foreach ($messages as $msg) {
                if (isset($msg['key']['fromMe']) && $msg['key']['fromMe']) {
                    continue;
                }

                $phone = $msg['key']['remoteJid'] ?? null;
                if (!$phone) {
                    continue;
                }

                $phone = preg_replace('/\D/', '', $phone);
                // No truncamos a 10 dígitos para no perder el código de país (ej. 549)

                // Handle different message formats
                $messageBody = $msg['message']['conversation'] ?? 
                             $msg['message']['extendedTextMessage']['text'] ?? 
                             $msg['message']['messageContextInfo'] ?? // v2 new format
                             '';

                $mediaUrl = null;
                if (isset($msg['message']['imageMessage'])) {
                    $mediaUrl = $msg['message']['imageMessage']['url'] ?? null;
                } elseif (isset($msg['message']['documentMessage'])) {
                    $mediaUrl = $msg['message']['documentMessage']['url'] ?? null;
                }

                $contactName = $msg['pushName'] ?? null;

                Log::info('Dispatching job', [
                    'tenant_id' => $tenantId,
                    'phone' => $phone,
                    'message' => substr($messageBody, 0, 30),
                ]);

                ProcessIncomingMessageJob::dispatch([
                    'tenant_id' => $tenantId,
                    'phone' => $phone,
                    'message' => $messageBody,
                    'media_url' => $mediaUrl,
                    'contact_name' => $contactName,
                ]);
            }

            return response()->json(['status' => 'queued', 'count' => count($messages)]);
        }

        return response()->json(['status' => 'ignored']);
    }
}
