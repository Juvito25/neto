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
        $instanceName = $request->header('X-Evolution-Instance');
        
        if (!$instanceName) {
            return response()->json(['error' => 'Instance not provided'], 400);
        }

        $instance = WhatsappInstance::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('instance_name', $instanceName)
            ->first();

        if (!$instance) {
            return response()->json(['error' => 'Instance not found'], 404);
        }

        $tenant = $instance->tenant;

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
            $messages = $payload['data']['messages'] ?? [];
            
            foreach ($messages as $msg) {
                if (isset($msg['key']['fromMe']) && $msg['key']['fromMe']) {
                    continue;
                }

                $phone = $msg['key']['remoteJid'] ?? null;
                if (!$phone) {
                    continue;
                }

                $phone = preg_replace('/\D/', '', $phone);
                if (strlen($phone) > 10) {
                    $phone = substr($phone, -10);
                }

                $messageBody = $msg['message']['conversation'] ?? 
                               $msg['message']['extendedTextMessage']['text'] ?? 
                               '';

                $mediaUrl = null;
                if (isset($msg['message']['imageMessage'])) {
                    $mediaUrl = $msg['message']['imageMessage']['url'] ?? null;
                } elseif (isset($msg['message']['documentMessage'])) {
                    $mediaUrl = $msg['message']['documentMessage']['url'] ?? null;
                }

                $contactName = $msg['pushName'] ?? null;

                ProcessIncomingMessageJob::dispatch([
                    'tenant_id' => $tenant->id,
                    'phone' => $phone,
                    'message' => $messageBody,
                    'media_url' => $mediaUrl,
                    'contact_name' => $contactName,
                ]);
            }

            return response()->json(['status' => 'queued']);
        }

        return response()->json(['status' => 'ignored']);
    }
}
