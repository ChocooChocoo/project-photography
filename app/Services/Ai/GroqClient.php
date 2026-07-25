<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Transport for the Groq chat-completions API.
 *
 * This is the only class in the application that reads the Groq API key. It
 * never returns provider error text to the caller and never logs request
 * payloads, response bodies, or headers -- only a status code and a short
 * reason code, so credentials and conversation content stay out of the logs.
 */
class GroqClient
{
    /**
     * Send a chat completion request.
     *
     * @param  array<int, array<string, string>>  $messages
     * @return array{ok: bool, text: string|null, tokens: int, reason: string|null}
     */
    public function chat(array $messages): array
    {
        $apiKey = (string) config('services.groq.api_key');

        if ($apiKey === '') {
            Log::warning('Groq assistant is not configured.');

            return $this->failure('not_configured');
        }

        $payload = [
            'model' => (string) config('services.groq.model'),
            'messages' => $messages,
            'max_tokens' => (int) config('services.groq.max_tokens', 400),
            'temperature' => (float) config('services.groq.temperature', 0.3),
            'stream' => false,
        ];

        // Keep the model's reasoning out of the reply body when supported.
        foreach (['reasoning_format', 'reasoning_effort'] as $option) {
            $value = (string) config("services.groq.{$option}", '');

            if ($value !== '') {
                $payload[$option] = $value;
            }
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout((int) config('services.groq.timeout', 20))
                ->retry(1, 500, throw: false)
                ->post(rtrim((string) config('services.groq.base_url'), '/').'/chat/completions', $payload);
        } catch (\Throwable $e) {
            // Exception messages can carry the request URL and headers, so only
            // the exception class is recorded.
            Log::error('Groq assistant request failed.', ['exception' => $e::class]);

            return $this->failure('transport_error');
        }

        if ($response->status() === 429) {
            Log::warning('Groq assistant rate limited by provider.', ['status' => 429]);

            return $this->failure('provider_rate_limited');
        }

        if (! $response->successful()) {
            Log::error('Groq assistant returned an error status.', ['status' => $response->status()]);

            return $this->failure('provider_error');
        }

        $payload = $response->json();
        $text = data_get($payload, 'choices.0.message.content');

        if (! is_string($text) || trim($text) === '') {
            Log::error('Groq assistant returned an unusable payload.', ['status' => $response->status()]);

            return $this->failure('invalid_payload');
        }

        return [
            'ok' => true,
            'text' => $text,
            'tokens' => (int) data_get($payload, 'usage.total_tokens', 0),
            'reason' => null,
        ];
    }

    /**
     * @return array{ok: bool, text: null, tokens: int, reason: string}
     */
    protected function failure(string $reason): array
    {
        return [
            'ok' => false,
            'text' => null,
            'tokens' => 0,
            'reason' => $reason,
        ];
    }
}
