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
        
        // Log raw body for debugging
        $rawBody = $request->getContent();
        file_put_contents('/tmp/webhook.log', date('Y-m-d H:i:s') . ' RAW: ' . substr($rawBody, 0, 500) . "\n", FILE_APPEND);
        
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
        $providedKey = $request->header('apikey') ?? $request->get('apikey');
        
        Log::info('Webhook debug', [
            'provided_key' => $providedKey ? substr($providedKey, 0, 8) : 'NULL',
            'expected_key' => $evolutionKey ? substr($evolutionKey, 0, 8) : 'NULL',
            'all_headers' => array_keys($request->headers->all()),
            'body_keys' => array_keys($request->all()),
        ]);
        
        // Allow requests from Evolution container (multiple IPs for redundancy)
        $requestIp = $request->ip();
        
        // Allow requests from Evolution container (multiple IPs for redundancy) and whole docker network
        $isFromEvolution = str_starts_with($requestIp, '172.18.');
        
        if (!$isFromEvolution && $providedKey !== $evolutionKey) {
            Log::warning('Webhook Security: Invalid apikey', [
                'provided' => substr($providedKey ?? 'null', 0, 5),
                'ip' => $requestIp
            ]);
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

        // First try exact match
        $instance = WhatsappInstance::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('instance_name', $instanceName)
            ->first();

        // If no exact match, try prefix match (handles timestamp differences)
        if (!$instance && (strpos($instanceName, 'neto_') === 0 || strpos($instanceName, 'neto-') === 0)) {
            // Extract UUID - handle both hyphen and underscore as separators
            $tenantUuid = preg_replace('/[_-]\d+$/', '', $instanceName); // Remove timestamp suffix
            $tenantUuid = str_replace(['neto_', 'neto-'], '', $tenantUuid); // Remove prefix to get just UUID
            
            Log::debug('Extracted tenant UUID from instance name', ['tenantUuid' => $tenantUuid]);
            
            $instance = WhatsappInstance::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                ->where('tenant_id', $tenantUuid)
                ->first();
            
            Log::debug('Fallback to tenant_id match', [
                'tenantUuid' => $tenantUuid,
                'found' => $instance ? $instance->id : 'null'
            ]);
        }

        Log::info('Instance found', ['instance' => $instance ? $instance->id : 'null']);
        
        if (!$instance) {
            Log::warning('Webhook: Instance not found in database', ['instanceName' => $instanceName]);
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
                $rawState = strtolower($payload['data']['state']);
                $statusMap = [
                    'open' => 'connected',
                    'connected' => 'connected',
                    'close' => 'disconnected',
                    'disconnected' => 'disconnected',
                ];
                $status = $statusMap[$rawState] ?? 'connecting';
                
                Log::info('WhatsApp connection status update', [
                    'instance' => $instance->instance_name,
                    'raw_state' => $rawState,
                    'new_status' => $status
                ]);

                $instance->update([
                    'status' => $status,
                    'connected_at' => $status === 'connected' ? now() : null,
                ]);

                $tenant->update(['whatsapp_status' => $status]);
            }
            return response()->json(['status' => 'Connection updated']);
        }

        if (isset($payload['event']) && in_array($payload['event'], ['messages.upsert', 'MESSAGES_UPSERT'])) {
            // Evolution API format: can be in 'data' or 'data.messages'
            $data = $payload['data'] ?? [];
            $messages = [];
            
            if (isset($data['messages']) && is_array($data['messages'])) {
                $messages = $data['messages'];
            } elseif (isset($data['key'])) {
                $messages = [$data];
            } elseif (is_array($data) && isset($data[0]['key'])) {
                $messages = $data;
            }
            
            if (empty($messages)) {
                Log::warning('Webhook: No valid messages found in payload', ['payload_keys' => array_keys($payload)]);
                return response()->json(['status' => 'no_messages']);
            }
            
            Log::info('Processing WhatsApp messages', ['count' => count($messages)]);
            
            foreach ($messages as $msg) {
                // Skip if from me
                if (isset($msg['key']['fromMe']) && $msg['key']['fromMe']) {
                    continue;
                }

                $remoteJid = $msg['key']['remoteJid'] ?? null;
                if (!$remoteJid || str_contains($remoteJid, '@g.us') || str_contains($remoteJid, '@broadcast')) {
                    continue; // Skip groups and broadcasts
                }

                $phone = preg_replace('/\D/', '', $remoteJid);

                // Handle different message formats (v1, v2, Baileys)
                $messageContent = $msg['message'] ?? null;
                if (!$messageContent) continue;

                $messageBody = $messageContent['conversation'] ?? 
                             $messageContent['extendedTextMessage']['text'] ?? 
                             $messageContent['imageMessage']['caption'] ??
                             $messageContent['videoMessage']['caption'] ??
                             $messageContent['templateButtonReplyMessage']['selectedId'] ??
                             $messageContent['buttonsResponseMessage']['selectedButtonId'] ??
                             $messageContent['listResponseMessage']['singleSelectReply']['selectedRowId'] ??
                             '';

                // Si es un objeto complejo (v2 sometimes), intentar extraer el texto
                if (is_array($messageBody)) {
                    $messageBody = $messageBody['text'] ?? '';
                }

                $mediaUrl = null;
                if (isset($messageContent['imageMessage'])) {
                    $mediaUrl = $messageContent['imageMessage']['url'] ?? null;
                }

                $contactName = $msg['pushName'] ?? null;

                Log::info('Dispatching ProcessIncomingMessageJob', [
                    'tenant_id' => $tenantId,
                    'phone' => $phone,
                    'message_snippet' => substr((string)$messageBody, 0, 30),
                ]);

                ProcessIncomingMessageJob::dispatch([
                    'tenant_id' => $tenantId,
                    'phone' => $phone,
                    'message' => (string)$messageBody,
                    'media_url' => $mediaUrl,
                    'contact_name' => $contactName,
                ]);
            }

            return response()->json(['status' => 'queued', 'count' => count($messages)]);
        }

        return response()->json(['status' => 'ignored']);
    }
}
