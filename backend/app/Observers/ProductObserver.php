<?php

namespace App\Observers;

use App\Models\Product;

class ProductObserver
{
    public function created(Product $product): void
    {
        $this->generateEmbedding($product);
    }

    public function updated(Product $product): void
    {
        if ($product->isDirty(['name', 'description'])) {
            $this->generateEmbedding($product);
        }
    }

    private function generateEmbedding(Product $product): void
    {
        $apiKey = config('services.openai.key');

        if (!$apiKey) {
            return;
        }

        $text = $product->name . ' ' . $product->description;

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
                ->post('https://api.openai.com/v1/embeddings', [
                    'model' => 'text-embedding-3-small',
                    'input' => $text,
                ]);

            if ($response->failed()) {
                return;
            }

            $embedding = $response->json('data.0.embedding');

            $product->update(['embedding' => $embedding]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('ProductObserver: Failed to generate embedding', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
