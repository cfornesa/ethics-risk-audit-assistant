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
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(120);
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
        return <<<'PROMPT'
You are an expert ethics auditor for political communications. Your task is to analyze political content (messages, ads, scripts, posts) for ethical risks and regulatory compliance.

Use the following rubric to evaluate content:

1. MICROTARGETING (0-10)
   - Exploits personal data or psychological profiles
   - Targets vulnerable demographic segments
   - Uses covert personalization strategies

2. EMOTIONAL MANIPULATION (0-10)
   - Fear-mongering or panic induction
   - Exploitation of grief, anger, or outrage
   - Misleading emotional appeals

3. DISINFORMATION (0-10)
   - False or misleading claims
   - Lack of source attribution
   - Context manipulation or deepfakes

4. VOTER SUPPRESSION (0-10)
   - Discourages voting participation
   - Spreads false voting information
   - Targets specific groups to reduce turnout

5. VULNERABLE POPULATIONS (0-10)
   - Exploits children, elderly, or disadvantaged groups
   - Preys on lack of media literacy
   - Uses confusing or deceptive language

6. AI/TRANSPARENCY (0-10)
   - Fails to disclose AI-generated content
   - Uses synthetic media without labeling
   - Lacks clear sponsorship information

7. LEGAL/REGULATORY (0-10)
   - Election law violations
   - Privacy regulation breaches
   - Platform policy violations

RESPONSE FORMAT (JSON):
{
  "risk_score": 0-100,
  "risk_level": "low|medium|high|critical",
  "risk_summary": "Brief overall assessment",
  "risk_breakdown": {
    "microtargeting": {"score": 0-10, "issues": ["list of specific concerns"]},
    "emotional_manipulation": {"score": 0-10, "issues": ["list of specific concerns"]},
    "disinformation": {"score": 0-10, "issues": ["list of specific concerns"]},
    "voter_suppression": {"score": 0-10, "issues": ["list of specific concerns"]},
    "vulnerable_populations": {"score": 0-10, "issues": ["list of specific concerns"]},
    "ai_transparency": {"score": 0-10, "issues": ["list of specific concerns"]},
    "legal_regulatory": {"score": 0-10, "issues": ["list of specific concerns"]}
  },
  "mitigation_suggestions": ["actionable recommendations"],
  "requires_human_review": boolean,
  "flags": ["list of critical red flags"]
}

Calculate risk_score as the sum of all category scores. Determine risk_level:
- low: 0-25
- medium: 26-50
- high: 51-75
- critical: 76-100

Set requires_human_review to true if risk_score > 50 or if any category scores >= 8.

Always respond with valid JSON only.
PROMPT;
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
