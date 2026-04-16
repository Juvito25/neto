<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\Contact;
use App\Models\Message;
use App\Models\WhatsappInstance;
use App\Models\Catalog;
use App\Models\CatalogItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessIncomingMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public array $messageData
    ) {}

    public function handle(): void
    {
        $tenantId = $this->messageData['tenant_id'] ?? null;
        $phone = $this->messageData['phone'] ?? null;
        $messageBody = $this->messageData['message'] ?? '';
        $mediaUrl = $this->messageData['media_url'] ?? null;

        if (!$tenantId || !$phone) {
            Log::error('ProcessIncomingMessageJob: missing tenant_id or phone', $this->messageData);
            return;
        }

        $tenant = Tenant::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->find($tenantId);
        
        if (!$tenant) {
            Log::error('ProcessIncomingMessageJob: tenant not found', ['tenant_id' => $tenantId]);
            return;
        }

        if (!$tenant->canUseBot()) {
            Log::info('Bot detenido: trial vencido o suscripción inactiva', [
                'tenant_id'           => $tenantId,
                'subscription_status' => $tenant->subscription_status,
                'trial_ends_at'       => $tenant->trial_ends_at,
            ]);
            return;
        }

        if ($tenant->hasReachedLimit()) {
            $this->sendMessage($tenant, $phone, "Has alcanzado el límite de mensajes de tu plan. ¡升级á tu plan para continuar!");
            return;
        }

        $contact = Contact::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('phone', $phone)
            ->first();

        if (!$contact) {
            $contact = Contact::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->create([
                'tenant_id' => $tenantId,
                'phone' => $phone,
                'name' => $this->messageData['contact_name'] ?? null,
            ]);
        }

        Message::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->create([
            'tenant_id' => $tenantId,
            'contact_id' => $contact->id,
            'direction' => 'in',
            'body' => $messageBody,
            'media_url' => $mediaUrl,
        ]);

        $catalogContext = $this->buildCatalogContext($tenantId);

        $systemPrompt = $this->buildSystemPrompt($tenant, $catalogContext);
        $conversationHistory = $this->buildConversationHistory($tenantId, $contact->id);

        $startTime = microtime(true);
        $originalMessage = $messageBody;
        $messageBody = $this->sanitizeMessage($messageBody);

        $response = $this->callGroq($systemPrompt, $conversationHistory, $messageBody);

        $duration = microtime(true) - $startTime;

        if ($response) {
            $replyContent = $response['content'];

            // ULTRA aggressive cleanup - remove anything that looks like [number] at the end
            // Also handle newlines before the bracket
            $replyContent = preg_replace('/\n?\s*\[\d{3,7}\]\s*$/', '', $replyContent);
            $replyContent = preg_replace('/\s*\[\d{3,7}\]/', '', $replyContent);
            $replyContent = preg_replace('/[\*\[]?\s*VENTA_CERRADA:\s*.*?no disponible.*?\s*\|\s*.*?no disponible.*?\s*\|\s*.*?no disponible.*?\]?\s*$/im', '', $replyContent);
            $replyContent = preg_replace('/[\*\[]?\s*VENTA_CERRADA:\s*.*?no disponible.*?\s*\|\s*.*?no disponible.*?\s*\|\s*.*?no disponible.*?\]?\s*/im', '', $replyContent);
            $replyContent = preg_replace('/[\*\[]?\s*VENTA_CERRADA:.*?(?:transfer|cash)\s*\|.*?\]?\s*$/im', '', $replyContent);
            $replyContent = preg_replace('/[\*\[]?\s*VENTA_CERRADA:.*?(?:transfer|cash)\s*\|.*?\]?\s*/im', '', $replyContent);
            $replyContent = trim($replyContent);

            // Only create sale if VENTA_CERRADA was in the ORIGINAL response AND user sent a confirmation keyword
            // Common confirmation keywords from users
            $userMessage = strtolower(trim($messageBody));
            $confirmationKeywords = ['si', 'sí', 'confirmo', 'ok', 'dale', 'perfecto', 'de acuerdo', 'está bien', 'por transferencia', 'transferencia', 'efectivo', 'pago', 'quiero', 'sí, por favor'];
            
            $isConfirmation = false;
            foreach ($confirmationKeywords as $keyword) {
                if (strpos($userMessage, $keyword) !== false) {
                    $isConfirmation = true;
                    break;
                }
            }
            
            // Only create sale if: user explicitly confirmed AND original response had VENTA_CERRADA (valid, not placeholder)
            $hasVentaCerrada = preg_match('/VENTA_CERRADA:/i', $response['content'] ?? '');
            
            // Parse VENTA_CERRADA but reject "(no disponible)" placeholders
            if ($hasVentaCerrada && $isConfirmation && preg_match('/VENTA_CERRADA:\s*(.*?)\s*\|\s*(transfer|cash)\s*\|\s*([\d\.,]+)/i', $response['content'], $matches)) {
                $itemsDesc = trim($matches[1]);
                $paymentMethod = strtolower(trim($matches[2]));
                $amount = str_replace(',', '.', preg_replace('/[^0-9\.,]/', '', $matches[3]));

                // Skip if placeholder values
                if (stripos($itemsDesc, 'no disponible') !== false || stripos($amount, 'no disponible') !== false) {
                    Log::info('Sale skipped: placeholder values detected', [
                        'items' => $itemsDesc,
                        'amount' => $amount,
                    ]);
                } else {
                    // Prevent duplicate sales: check if there's a recent pending sale from this contact
                    $recentSale = \App\Models\Sale::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                        ->where('tenant_id', $tenantId)
                        ->where('contact_id', $contact->id)
                        ->where('status', 'pending')
                        ->where('items_description', $itemsDesc)
                        ->where('total_amount', $amount)
                        ->where('created_at', '>', now()->subMinutes(5))
                        ->first();
                    
                    if ($recentSale) {
                        Log::info('Sale skipped: duplicate detected', [
                            'items' => $itemsDesc,
                            'amount' => $amount,
                        ]);
                    } else {
                        \App\Models\Sale::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->create([
                            'tenant_id' => $tenantId,
                            'contact_id' => $contact->id,
                            'items_description' => $itemsDesc,
                            'payment_method' => $paymentMethod,
                            'status' => 'pending',
                            'total_amount' => $amount,
                        ]);
                        
                        // Increment sales counter on tenant
                        $tenant->increment('sales_count');
                        
                        Log::info('Sale created from user confirmation', [
                            'tenant_id' => $tenantId,
                            'contact_id' => $contact->id,
                            'amount' => $amount,
                        ]);
                    }
                }
            }

            Message::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->create([
                'tenant_id' => $tenantId,
                'contact_id' => $contact->id,
                'direction' => 'out',
                'body' => $replyContent,
                'tokens_used' => $response['tokens'],
            ]);

            $tenant->increment('messages_used', $response['tokens']);

            Log::info('Message processed', [
                'tenant_id' => $tenantId,
                'phone' => $phone,
                'duration_ms' => round($duration * 1000, 2),
                'tokens' => $response['tokens'],
                'sanitized' => $originalMessage !== $messageBody,
            ]);

            $this->sendMessage($tenant, $phone, $replyContent);
        }
    }



    private function buildCatalogContext(string $tenantId): string
    {
        $catalog = Catalog::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->first();

        if (!$catalog) {
            return '';
        }

        $items = CatalogItem::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('catalog_id', $catalog->id)
            ->limit(50)
            ->get();

        if ($items->isEmpty()) {
            return '';
        }

        $context = "\n\n📦 CATÁLOGO DE " . strtoupper($catalog->type) . ":\n";

        foreach ($items as $item) {
            $context .= "• {$item->name}";
            if ($item->price) {
                $context .= " - \${$item->price}";
            }
            if ($item->quantity !== null) {
                $context .= " (Stock: {$item->quantity})";
            }
            if ($item->duration_minutes) {
                $context .= " - {$item->duration_minutes} min";
            }
            if ($item->description) {
                $context .= "\n  {$item->description}";
            }
            $context .= "\n";
        }

        return $context;
    }

    private function buildSystemPrompt(Tenant $tenant, string $catalogContext = ''): string
    {
        $prompt = "Vos sos el asistente virtual de *{$tenant->business_name}*. SÉ BREVE Y DIRECTO. No exageres con saludos largos ni saques conclusiones sobre lo que el cliente no dijo.";
        
        if ($tenant->description) {
            $prompt .= "\n\nInformación del negocio: {$tenant->description}";
        }

        if ($tenant->business_hours) {
            $hours = is_array($tenant->business_hours) ? $tenant->business_hours : json_decode($tenant->business_hours, true);
            $prompt .= "\n\nHorarios de atención:";
            foreach ($hours as $day => $schedule) {
                if (!empty($schedule['open'])) {
                    $prompt .= "\n- {$day}: {$schedule['open']} - {$schedule['close']}";
                }
            }
        }

        if ($tenant->faqs) {
            $faqs = is_array($tenant->faqs) ? $tenant->faqs : json_decode($tenant->faqs, true);
            if (is_array($faqs) && count($faqs) > 0) {
                $prompt .= "\n\nPreguntas frecuentes:";
                foreach ($faqs as $faq) {
                    if (isset($faq['question']) && isset($faq['answer'])) {
                        $prompt .= "\nQ: {$faq['question']}\nA: {$faq['answer']}";
                    }
                }
            }
        }

        if ($tenant->custom_prompt) {
            $prompt .= "\n\nInstrucciones adicionales: {$tenant->custom_prompt}";
        }

        $prompt .= "\n\nRespondé de manera amable, concise y útil.";
        $prompt .= $catalogContext;

        if ($tenant->payment_transfer_enabled || $tenant->payment_cash_enabled) {
            $prompt .= "\n\nMEDIOS DE PAGO DE TU NEGOCIO (PARA COBRAR):\n";
            if ($tenant->payment_transfer_enabled) {
                $prompt .= "- Transferencia bancaria. TUS DATOS PARA RECIBIR EL PAGO SON:\n";
                if ($tenant->payment_transfer_cbu) $prompt .= "  CBU/Alias: {$tenant->payment_transfer_cbu}\n";
                if ($tenant->payment_transfer_name) $prompt .= "  Titular: {$tenant->payment_transfer_name}\n";
                if ($tenant->payment_transfer_bank) $prompt .= "  Banco: {$tenant->payment_transfer_bank}\n";
                $prompt .= "  -> CRÍTICO: Entregale siempre ESTOS datos al cliente para que realice la transferencia. ¡NUNCA le pidas su propio CBU al cliente!\n";
            }
            if ($tenant->payment_cash_enabled) {
                $prompt .= "- Pago al retirar/efectivo.";
                if ($tenant->payment_cash_note) $prompt .= " (Indicación para darle al cliente: {$tenant->payment_cash_note})\n";
            }

$prompt .= "\nFLUJO DE VENTAS (IMPORTANTE):
1. Cuando el cliente pregunte por un producto oiga el precio, dále el precio y simplemente preguntale '¿Querés encargarlo?'
2. SOLO cuando el cliente confirme que quiere comprar (dice sí, dale, ok, quiero, lo quiero, etc.), ahí sí preguntale cómo quiere pagar.
3. Cuando el cliente elija el medio de pago, ahí sí entregá tus datos bancarios Y después de eso includí el código VENTA_CERRADA.
4. Cuando el cliente confirme que realizó la transferencia o que irá a retirar, dale un mensaje de confirmación final.

REGLAS CRÍTICAS:
- NO des datos bancarios hasta que el cliente confirme que quiere comprar. Si pregunta precio, dá el precio y espera confirmación.
- El código VENTA_CERRADA debe ir SOLO después de dar los datos de pago, cuando el cliente ya eligió cómo pagar.
- No pongas VENTA_CERRADA si el cliente solo está preguntando información o no confirmó la compra.

Formato para VENTA_CERRADA (solo cuando la venta está realmente cerrada):
[VENTA_CERRADA: (descripción) | (transfer/cash) | (monto)]

Ejemplo correcto:
[VENTA_CERRADA: 10 souvenirs de Ben10 | transfer | 1500]";
        }

        return $prompt;
    }

    private function buildConversationHistory(string $tenantId, string $contactId): array
    {
        $messages = Message::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('contact_id', $contactId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();

        return $messages->map(function ($msg) {
            return [
                'role' => $msg->direction === 'in' ? 'user' : 'assistant',
                'content' => $msg->body,
            ];
        })->toArray();
    }

    private function callGroq(string $systemPrompt, array $conversationHistory, string $currentMessage): ?array
    {
        $apiKey = config('services.groq.key');
        
        if (!$apiKey) {
            Log::error('ProcessIncomingMessageJob: GROQ_API_KEY not configured');
            return null;
        }

        $messages = array_merge($conversationHistory, [
            ['role' => 'user', 'content' => "### USER MESSAGE BEGIN ###\n{$currentMessage}\n### USER MESSAGE END ###"]
        ]);

        try {
            // Groq es OpenAI-compatible
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'llama-3.1-8b-instant',
                'max_tokens' => 500,
                'temperature' => 0.7,
                'messages' => array_merge(
                    [['role' => 'system', 'content' => $systemPrompt]],
                    $messages
                ),
            ]);

            if ($response->failed()) {
                Log::error('ProcessIncomingMessageJob: Groq API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            
            return [
                'content' => $data['choices'][0]['message']['content'] ?? 'Disculpa, tuve un problema al procesar tu mensaje.',
                'tokens' => $data['usage']['completion_tokens'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('ProcessIncomingMessageJob: Exception calling Groq', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function sanitizeMessage(string $message): string
    {
        // Simple prompt injection protection
        $keywords = [
            'ignore all previous instructions',
            'ignore previous instructions',
            'forget everything',
            'forget all instructions',
            'eres un',
            'ahora eres',
            'you are now',
            'system prompt',
            'instrucciones del sistema',
        ];

        $sanitized = $message;
        foreach ($keywords as $keyword) {
            if (stripos($sanitized, $keyword) !== false) {
                Log::warning('Potential Prompt Injection detected', [
                    'tenant_id' => $this->messageData['tenant_id'] ?? null,
                    'keyword' => $keyword,
                ]);
                $sanitized = str_ireplace($keyword, '[BLOQUEADO]', $sanitized);
            }
        }

        return $sanitized;
    }

    private function sendMessage(Tenant $tenant, string $phone, string $message): void
    {
        if (empty(trim($message))) {
            Log::warning('ProcessIncomingMessageJob: Skipping empty message');
            return;
        }

        $instance = WhatsappInstance::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$instance || !$instance->isConnected()) {
            Log::warning('ProcessIncomingMessageJob: WhatsApp not connected', [
                'tenant_id' => $tenant->id,
            ]);
            return;
        }

        $evolutionUrl = config('services.evolution.url');
        $evolutionKey = config('services.evolution.key');

        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        
        // Si no tiene el código de país (54) y código whatsapp (9), lo agregamos como default
        if (strlen($cleanPhone) <= 10) {
            $cleanPhone = '549' . $cleanPhone;
        }

        try {
            $response = Http::withHeaders([
                'apikey' => $evolutionKey,
                'Content-Type' => 'application/json',
            ])->post("{$evolutionUrl}/message/sendText/{$instance->instance_name}", [
                'number' => $cleanPhone,
                'text' => $message,
            ]);

            Log::info('Evolution sendText response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('ProcessIncomingMessageJob: Failed to send message', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
