<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected ?string $apiKey;
    protected string $baseUrl = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.groq.api_key');
    }

    public function ask(string $prompt, string $model = 'llama-3.1-8b-instant'): string
    {
        if (empty($this->apiKey)) {
            throw new \Exception('Groq API Key is missing');
        }

        $maxRetries = 3;
        $retryDelay = 5; // ثواني

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::timeout(90)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post($this->baseUrl, [
                        'model' => $model,
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'You are an expert CV analysis and optimization assistant. Always respond with valid JSON only.',
                            ],
                            ['role' => 'user', 'content' => $prompt],
                        ],
                        'temperature' => 0.3,
                        'max_tokens' => 4096,
                    ]);

                if ($response->status() === 429) {
                    $error = $response->json('error.message', 'Rate limit');
                    Log::warning("Groq Rate Limit hit (attempt $attempt)", ['error' => $error]);

                    if ($attempt < $maxRetries) {
                        sleep($retryDelay * $attempt); // 5s, 10s, 15s
                        continue;
                    }

                    throw new \Exception('Rate limit reached on Groq. Please wait a minute and try again.');
                }

                if (!$response->successful()) {
                    throw new \Exception('Groq API Error [' . $response->status() . ']: ' . $response->body());
                }

                $data = $response->json();

                if (!isset($data['choices'][0]['message']['content'])) {
                    throw new \Exception('Invalid response from Groq');
                }

                return $data['choices'][0]['message']['content'];

            } catch (\Exception $e) {
                if ($attempt === $maxRetries) {
                    Log::error('AI Service final failure', ['error' => $e->getMessage()]);
                    throw new \Exception('Failed to get AI response: ' . $e->getMessage());
                }
                sleep(2);
            }
        }

        throw new \Exception('Failed to get AI response after retries');
    }
}
