<?php

namespace Tests\Feature\Ai;

use App\Services\Ai\GroqClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Credential handling for the Groq transport: a rejected key must fail safe
 * and log a distinct warning, never the key itself.
 */
class GroqClientCredentialTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.groq.api_key' => 'gsk_test_key_not_real',
            'services.groq.model' => 'qwen/qwen3.6-27b',
            'services.groq.base_url' => 'https://api.groq.com/openai/v1',
        ]);

        Http::preventStrayRequests();
    }

    public function test_ping_succeeds_with_a_valid_key(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'pong']]],
            ], 200),
        ]);

        $result = (new GroqClient)->ping();

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
        $this->assertNull($result['reason']);
    }

    public function test_ping_reports_invalid_credentials_on_401(): void
    {
        Log::spy();
        Http::fake(['api.groq.com/*' => Http::response(['error' => 'invalid api key'], 401)]);

        $result = (new GroqClient)->ping();

        $this->assertFalse($result['ok']);
        $this->assertSame(401, $result['status']);
        $this->assertSame('invalid_credentials', $result['reason']);

        Log::shouldHaveReceived('warning')
            ->with('Groq assistant rejected the API key; it is invalid or expired.', ['status' => 401])
            ->once();
    }

    public function test_chat_returns_guarded_failure_on_403_without_leaking_the_key(): void
    {
        Log::spy();
        Http::fake(['api.groq.com/*' => Http::response(['error' => 'forbidden'], 403)]);

        $result = (new GroqClient)->chat([
            ['role' => 'user', 'content' => 'Hello'],
        ]);

        $this->assertFalse($result['ok']);
        $this->assertNull($result['text']);
        $this->assertSame('invalid_credentials', $result['reason']);

        Log::shouldHaveReceived('warning')
            ->with('Groq assistant rejected the API key; it is invalid or expired.', ['status' => 403])
            ->once();
    }
}
