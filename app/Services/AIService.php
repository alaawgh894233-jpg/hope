<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AIService
{
    public function ask(string $prompt): string
    {
        try {
            $response = Http::withToken(env('GROQ_API_KEY'))
                ->timeout(60)
                ->retry(1, 200)
                ->post(
                    'https://api.groq.com/openai/v1/chat/completions',
                    [
                        'model' => 'llama-3.1-8b-instant',
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'You are a strict JSON generator. Output ONLY valid JSON. No markdown, no text, no explanation, no backticks. If you cannot comply, return {}.'
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt
                            ]
                        ],
                        'temperature' => 0.2,
                    ]
                );

            $content = data_get($response->json(), 'choices.0.message.content');

            return $content ?? '{}';

        } catch (\Throwable $e) {
            return '{}';
        }
    }
}
