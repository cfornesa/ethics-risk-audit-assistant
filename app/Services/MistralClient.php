<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MistralClient
{
    protected string $apiKey;
    protected string $apiBase;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.mistral.api_key');
        $this->apiBase = config('services.mistral.api_base', 'https://api.mistral.ai/v1');
        $this->model = config('services.mistral.model', 'ministral-3-14b-reasoning-2512');
    }

    protected function client(): PendingRequest
    {
        $client = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(120);

        // Prevent API key from being logged in debug/development environments
        if (config('app.debug')) {
            Log::warning('Mistral API client initialized with API key in debug mode. Ensure API keys are not logged in production.');
        }

        return $client;
    }

    public function chat(array $messages, array $options = []): array
    {
        $payload = array_merge([
            'model' => $options['model'] ?? $this->model,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 4000,
        ], $options);

        if (isset($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        }

        Log::info('Mistral API request', [
            'model' => $payload['model'],
            'messages_count' => count($messages),
        ]);

        try {
            $response = $this->client()->post($this->apiBase . '/chat/completions', $payload);

            if (!$response->successful()) {
                Log::error('Mistral API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \RuntimeException(
                    'Mistral API request failed: ' . $response->body(),
                    $response->status()
                );
            }

            $data = $response->json();

            Log::info('Mistral API response received', [
                'usage' => $data['usage'] ?? null,
            ]);

            return $data;
        } catch (\Exception $e) {
            Log::error('Mistral API exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function extractContent(array $response): string
    {
        return $response['choices'][0]['message']['content'] ?? '';
    }

    public function ethicsAudit(string $content, string $contentType = 'message'): array
    {
        $systemPrompt = $this->getEthicsRubricPrompt();
        $userPrompt = $this->buildAuditPrompt($content, $contentType);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        $response = $this->chat($messages, [
            'temperature' => 0.3,
            'response_format' => ['type' => 'json_object'],
        ]);

        $content = $this->extractContent($response);

        return json_decode($content, true) ?? [];
    }

    protected function getEthicsRubricPrompt(): string
    {
        return config('ethics.rubric_system_prompt');
    }

    protected function buildAuditPrompt(string $content, string $contentType): string
    {
        return <<<PROMPT
Please audit the following political {$contentType} for ethics/risk concerns:

---
{$content}
---

Provide a comprehensive ethics assessment using the rubric, returning only valid JSON.
PROMPT;
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->chat([
                ['role' => 'user', 'content' => 'Respond with "OK" if you receive this message.']
            ], [
                'max_tokens' => 10,
            ]);

            return isset($response['choices'][0]['message']['content']);
        } catch (\Exception $e) {
            Log::error('Mistral connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
