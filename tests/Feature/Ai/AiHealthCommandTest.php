<?php

namespace Tests\Feature\Ai;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The ai:health command must report a safe status and exit non-zero when the
 * provider key is missing or rejected.
 */
class AiHealthCommandTest extends TestCase
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

    public function test_command_reports_ok_and_exits_zero_for_a_valid_key(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'pong']]],
            ], 200),
        ]);

        $this->artisan('ai:health')
            ->expectsOutputToContain('Groq key: ok')
            ->expectsOutputToContain('Groq model: qwen/qwen3.6-27b')
            ->assertExitCode(0);
    }

    public function test_command_reports_invalid_key_and_exits_non_zero_for_401(): void
    {
        Http::fake(['api.groq.com/*' => Http::response(['error' => 'invalid api key'], 401)]);

        $this->artisan('ai:health')
            ->expectsOutputToContain('Groq key: invalid (401)')
            ->expectsOutputToContain('Groq model: qwen/qwen3.6-27b')
            ->assertExitCode(1);
    }

    public function test_command_reports_not_configured_without_a_key(): void
    {
        config(['services.groq.api_key' => '']);
        Http::fake();

        $this->artisan('ai:health')
            ->expectsOutputToContain('Groq key: not configured')
            ->assertExitCode(1);

        Http::assertNothingSent();
    }

    public function test_command_never_prints_the_key(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'pong']]],
            ], 200),
        ]);

        $this->artisan('ai:health')->assertExitCode(0);

        $this->assertStringNotContainsString('gsk_test_key_not_real', Artisan::output());
    }
}
