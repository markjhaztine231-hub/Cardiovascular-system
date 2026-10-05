<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class HuggingFaceService
{
    protected string $token;

    protected string $baseUrl = 'https://api-inference.huggingface.co/models';

    public function __construct()
    {
        $this->token = config('services.hf.token');
    }

    public function chat(string $model, array $messages, array $options = []): array
    {
        $payload = array_merge([
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.2,
            'max_tokens' => 8000,
        ], $options);

        $response = Http::withToken($this->token)
            ->timeout(120)
            ->post("{$this->baseUrl}/{$model}/v1/chat/completions", $payload);

        if ($response->failed()) {
            throw new \Exception('HuggingFace API Error: '.$response->body());
        }

        return $response->json();
    }

    public function generateText(string $model, string $systemPrompt, string $userPrompt, array $options = []): string
    {
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        $result = $this->chat($model, $messages, $options);

        return $result['choices'][0]['message']['content'] ?? 'No text generated.';
    }
}
