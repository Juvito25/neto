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

        if ($tenant->isTrialExpired() && !$tenant->isActive()) {
            Log::info('Bot detenido: trial vencido', ['tenant_id' => $tenantId]);
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

        $response = $this->callGroq($systemPrompt, $conversationHistory, $messageBody);

        if ($response) {
            $replyContent = $response['content'];

            if (preg_match('/VENTA_CERRADA:\s*(.*?)\s*\|\s*(transfer|cash)/i', $replyContent, $matches)) {
                $itemsDesc = trim($matches[1]);
                $paymentMethod = strtolower(trim($matches[2]));

                \App\Models\Sale::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->create([
                    'tenant_id' => $tenantId,
                    'contact_id' => $contact->id,
                    'items_description' => $itemsDesc,
                    'payment_method' => $paymentMethod,
                    'status' => 'pending',
                ]);

                // Limpiar tag de la respuesta del bot independientemente de corchetes o asteriscos
                $replyContent = trim(preg_replace('/[\*\[]?\s*VENTA_CERRADA:.*?\|.*?(?:transfer|cash)\s*[\*\]]?/i', '', $replyContent));
            }

            Message::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->create([
                'tenant_id' => $tenantId,
                'contact_id' => $contact->id,
                'direction' => 'out',
                'body' => $replyContent,
                'tokens_used' => $response['tokens'],
            ]);

            $tenant->increment('messages_used', $response['tokens']);

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
        $prompt = "Vos sos el asistente virtual de *{$tenant->business_name}*.";
        
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

            $prompt .= "\nINSTRUCCIONES PARA CERRAR VENTAS:
Cuando el cliente confirme su pedido, preguntale con cuál de los medios de pago de arriba prefiere pagar.
Si elige transferencia, BRINDALE tus datos bancarios para que envíe el importe.
Una vez que el cliente confirme que enviará el abono o que lo retirará, dale un mensaje de confirmación amigable.

REGLA OBLIGATORIA DEL SISTEMA (CRÍTICO):
Siempre que se cierre una venta (es decir, el cliente ya eligió qué comprar y cómo pagar, y tú estás confirmando el pedido), DEBES incluir al final de tu respuesta, en una línea separada, el siguiente bloque exacto, reemplazando la descripción y método de pago (transfer o cash):
[VENTA_CERRADA: (descripción de los items) | transfer]
o
[VENTA_CERRADA: (descripción de los items) | cash]

Ejemplo correcto:
[VENTA_CERRADA: 2 docenas de media lunas de manteca | transfer]

No agregues este código si todavía estás preguntando detalles o si el cliente no confirmó. Solo ponlo en el mensaje final de confirmación de la venta.";
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
            ['role' => 'user', 'content' => $currentMessage]
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
