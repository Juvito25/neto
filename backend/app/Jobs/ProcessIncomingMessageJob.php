<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\Contact;
use App\Models\Message;
use App\Models\WhatsappInstance;
use App\Actions\SearchProductAction;
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

    public function handle(SearchProductAction $searchProduct): void
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
            $this->sendMessage($tenant, $phone, "Tu servicio está pausado. Renová tu plan para continuar usando el bot.");
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

        $isProductQuery = $this->looksLikeProductQuery($messageBody);
        
        $productsContext = '';
        if ($isProductQuery) {
            $products = $searchProduct($tenantId, $messageBody);
            if ($products->isNotEmpty()) {
                $productsContext = "\n\nProductos disponibles:\n";
                foreach ($products as $product) {
                    $productsContext .= "- {$product->name}: \${$product->price}";
                    if ($product->stock !== null) {
                        $productsContext .= " (Stock: {$product->stock})";
                    }
                    if ($product->description) {
                        $productsContext .= "\n  {$product->description}";
                    }
                    $productsContext .= "\n";
                }
            }
        }

        $systemPrompt = $this->buildSystemPrompt($tenant, $productsContext);
        $conversationHistory = $this->buildConversationHistory($tenantId, $contact->id);

        $response = $this->callClaude($systemPrompt, $conversationHistory, $messageBody);

        if ($response) {
            Message::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->create([
                'tenant_id' => $tenantId,
                'contact_id' => $contact->id,
                'direction' => 'out',
                'body' => $response['content'],
                'tokens_used' => $response['tokens'],
            ]);

            $tenant->increment('messages_used', $response['tokens']);

            $this->sendMessage($tenant, $phone, $response['content']);
        }
    }

    private function looksLikeProductQuery(string $message): bool
    {
        $productKeywords = ['precio', 'tienen', 'cuánto', 'producto', 'comprar', 'stock', 'disponible', 'tenés', 'busco', 'necesito'];
        $messageLower = mb_strtolower($message);
        
        return collect($productKeywords)->contains(fn($keyword) => mb_strpos($messageLower, $keyword) !== false);
    }

    private function buildSystemPrompt(Tenant $tenant, string $productsContext = ''): string
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
        $prompt .= $productsContext;

        return $prompt;
    }

    private function buildConversationHistory(int $tenantId, int $contactId): array
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

    private function callClaude(string $systemPrompt, array $conversationHistory, string $currentMessage): ?array
    {
        $apiKey = config('services.anthropic.key');
        
        if (!$apiKey) {
            Log::error('ProcessIncomingMessageJob: ANTHROPIC_API_KEY not configured');
            return null;
        }

        $messages = array_merge($conversationHistory, [
            ['role' => 'user', 'content' => $currentMessage]
        ]);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-haiku-4-5',
                'max_tokens' => 500,
                'system' => $systemPrompt,
                'messages' => $messages,
            ]);

            if ($response->failed()) {
                Log::error('ProcessIncomingMessageJob: Claude API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            
            return [
                'content' => $data['content'][0]['text'] ?? 'Disculpa, tuve un problema al procesar tu mensaje.',
                'tokens' => $data['usage']['output_tokens'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('ProcessIncomingMessageJob: Exception calling Claude', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function sendMessage(Tenant $tenant, string $phone, string $message): void
    {
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

        try {
            Http::withHeaders([
                'apikey' => $evolutionKey,
                'Content-Type' => 'application/json',
            ])->post("{$evolutionUrl}/message/sendText/{$instance->instance_name}", [
                'number' => $phone,
                'text' => $message,
            ]);
        } catch (\Exception $e) {
            Log::error('ProcessIncomingMessageJob: Failed to send message', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
