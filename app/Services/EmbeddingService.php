<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EmbeddingService
{
    public function embed(string $text): array
    {
        $response = Http::post('http://127.0.0.1:8000/embed', [
            'text' => $text
        ]);

        if (!$response->successful()) {
            throw new \Exception($response->body());
        }

        return $response->json('vector') ?? [];
    }
}
