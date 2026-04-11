<?php

namespace App\Actions;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class SearchProductAction
{
    public function __invoke(string $tenantId, string $query, float $threshold = 0.75): \Illuminate\Database\Eloquent\Collection
    {
        $embedding = $this->generateEmbedding($query);

        if (!$embedding) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('tenant_id', $tenantId)
            ->where('active', true)
            ->whereNotNull('embedding')
            ->selectRaw('*, 1 - (embedding <=> ?) as similarity', [$embedding])
            ->having('similarity', '>=', $threshold)
            ->orderByDesc('similarity')
            ->limit(5)
            ->get();
    }

    private function generateEmbedding(string $text): ?array
    {
        $apiKey = config('services.openai.key');

        if (!$apiKey) {
            return null;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
                ->post('https://api.openai.com/v1/embeddings', [
                    'model' => 'text-embedding-3-small',
                    'input' => $text,
                ]);

            if ($response->failed()) {
                return null;
            }

            return $response->json('data.0.embedding');
        } catch (\Exception $e) {
            return null;
        }
    }
}
