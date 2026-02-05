<?php

namespace Tests\Unit\Services;

use App\Services\MistralClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MistralClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.mistral.api_key', 'test-api-key');
        Config::set('services.mistral.api_base', 'https://api.mistral.ai/v1');
        Config::set('services.mistral.model', 'test-model');
    }

    public function test_chat_sends_request_with_correct_payload(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Test response',
                        ],
                    ],
                ],
                'usage' => [
                    'total_tokens' => 100,
                ],
            ], 200),
        ]);

        $client = new MistralClient();
        $messages = [
            ['role' => 'user', 'content' => 'Test message'],
        ];

        $response = $client->chat($messages);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('choices', $response);
        $this->assertEquals('Test response', $response['choices'][0]['message']['content']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.mistral.ai/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-api-key')
                && $request->hasHeader('Content-Type', 'application/json')
                && $request['model'] === 'test-model'
                && $request['temperature'] === 0.7
                && $request['max_tokens'] === 4000;
        });
    }

    public function test_chat_accepts_custom_options(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Test response',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $client = new MistralClient();
        $messages = [
            ['role' => 'user', 'content' => 'Test message'],
        ];

        $client->chat($messages, [
            'temperature' => 0.5,
            'max_tokens' => 2000,
            'response_format' => ['type' => 'json_object'],
        ]);

        Http::assertSent(function ($request) {
            return $request['temperature'] === 0.5
                && $request['max_tokens'] === 2000
                && isset($request['response_format'])
                && $request['response_format']['type'] === 'json_object';
        });
    }

    public function test_chat_throws_exception_on_api_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'API error'], 500),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Mistral API request failed');

        $client = new MistralClient();
        $client->chat([['role' => 'user', 'content' => 'Test']]);
    }

    public function test_chat_logs_api_errors(): void
    {
        Log::shouldReceive('info')->twice();
        Log::shouldReceive('error')
            ->once()
            ->with('Mistral API error', \Mockery::on(function ($data) {
                return isset($data['status']) && $data['status'] === 500;
            }));

        Http::fake([
            '*' => Http::response(['error' => 'API error'], 500),
        ]);

        try {
            $client = new MistralClient();
            $client->chat([['role' => 'user', 'content' => 'Test']]);
        } catch (\RuntimeException $e) {
            // Expected exception
        }
    }

    public function test_chat_handles_network_exceptions(): void
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')
            ->once()
            ->with('Mistral API exception', \Mockery::on(function ($data) {
                return isset($data['message']);
            }));

        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Network error');

        $client = new MistralClient();
        $client->chat([['role' => 'user', 'content' => 'Test']]);
    }

    public function test_chat_handles_timeout(): void
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('error')->once();

        Http::fake(function () {
            throw new \Exception('Timeout');
        });

        $this->expectException(\Exception::class);

        $client = new MistralClient();
        $client->chat([['role' => 'user', 'content' => 'Test']]);
    }

    public function test_extract_content_returns_message_content(): void
    {
        $response = [
            'choices' => [
                [
                    'message' => [
                        'content' => 'Expected content',
                    ],
                ],
            ],
        ];

        $client = new MistralClient();
        $content = $client->extractContent($response);

        $this->assertEquals('Expected content', $content);
    }

    public function test_extract_content_returns_empty_string_for_missing_content(): void
    {
        $response = [
            'choices' => [],
        ];

        $client = new MistralClient();
        $content = $client->extractContent($response);

        $this->assertEquals('', $content);
    }

    public function test_ethics_audit_sends_correct_messages(): void
    {
        $mockResponse = [
            'risk_score' => 25,
            'risk_level' => 'low',
            'risk_summary' => 'No significant risks detected',
            'risk_breakdown' => [
                'microtargeting' => ['score' => 2, 'issues' => []],
                'emotional_manipulation' => ['score' => 3, 'issues' => []],
                'disinformation' => ['score' => 4, 'issues' => []],
                'voter_suppression' => ['score' => 5, 'issues' => []],
                'vulnerable_populations' => ['score' => 3, 'issues' => []],
                'ai_transparency' => ['score' => 4, 'issues' => []],
                'legal_regulatory' => ['score' => 4, 'issues' => []],
            ],
            'mitigation_suggestions' => [],
            'requires_human_review' => false,
            'flags' => [],
        ];

        Http::fake([
            '*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode($mockResponse),
                        ],
                    ],
                ],
            ], 200),
        ]);

        Config::set('ethics.rubric_system_prompt', 'Test rubric prompt');

        $client = new MistralClient();
        $result = $client->ethicsAudit('Test political content', 'message');

        $this->assertIsArray($result);
        $this->assertEquals(25, $result['risk_score']);
        $this->assertEquals('low', $result['risk_level']);

        Http::assertSent(function ($request) {
            $messages = $request['messages'];
            return count($messages) === 2
                && $messages[0]['role'] === 'system'
                && $messages[0]['content'] === 'Test rubric prompt'
                && $messages[1]['role'] === 'user'
                && str_contains($messages[1]['content'], 'Test political content')
                && $request['temperature'] === 0.3
                && isset($request['response_format'])
                && $request['response_format']['type'] === 'json_object';
        });
    }

    public function test_ethics_audit_handles_malformed_json_response(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Invalid JSON {',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $client = new MistralClient();
        $result = $client->ethicsAudit('Test content');

        $this->assertEquals([], $result);
    }

    public function test_test_connection_returns_true_on_success(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'OK',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $client = new MistralClient();
        $result = $client->testConnection();

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request['messages'][0]['content'] === 'Respond with "OK" if you receive this message.'
                && $request['max_tokens'] === 10;
        });
    }

    public function test_test_connection_returns_false_on_failure(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Mistral connection test failed', \Mockery::on(function ($data) {
                return isset($data['error']);
            }));

        Http::fake([
            '*' => Http::response(['error' => 'Connection failed'], 500),
        ]);

        $client = new MistralClient();
        $result = $client->testConnection();

        $this->assertFalse($result);
    }

    public function test_test_connection_returns_false_on_exception(): void
    {
        Log::shouldReceive('error')->once();

        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $client = new MistralClient();
        $result = $client->testConnection();

        $this->assertFalse($result);
    }

    public function test_client_logs_warning_in_debug_mode(): void
    {
        Config::set('app.debug', true);

        Log::shouldReceive('warning')
            ->once()
            ->with(\Mockery::on(function ($message) {
                return str_contains($message, 'API key in debug mode');
            }));

        Http::fake([
            '*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Test',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $client = new MistralClient();
        $client->chat([['role' => 'user', 'content' => 'Test']]);
    }

    public function test_client_does_not_log_warning_when_not_in_debug_mode(): void
    {
        Config::set('app.debug', false);

        Log::shouldReceive('warning')->never();
        Log::shouldReceive('info')->twice();

        Http::fake([
            '*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Test',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $client = new MistralClient();
        $client->chat([['role' => 'user', 'content' => 'Test']]);
    }
}
