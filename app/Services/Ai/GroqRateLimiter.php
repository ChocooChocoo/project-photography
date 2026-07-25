<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Cache;

/**
 * Request and token budget gate for the shared Groq API key.
 *
 * The key is application-wide, so the request/token windows are global; a
 * per-user window is layered on top so a single account cannot drain the
 * whole studio's daily allowance.
 *
 * ponytail: cache counters, not a DB table. Counters are advisory (a race can
 * overshoot by a request or two) which is why the configured caps sit under
 * the provider's real limits. Move to an atomic store if that ever matters.
 */
class GroqRateLimiter
{
    /**
     * Check every budget window before an API call is made.
     *
     * @return string|null Guard code when blocked, null when the call may proceed.
     */
    public function attempt(int $estimatedTokens, ?int $userId = null): ?string
    {
        $limits = (array) config('services.groq.limits');
        $minute = now()->format('YmdHi');
        $day = now()->format('Ymd');

        $windows = [
            ["groq:rpm:{$minute}", 1, (int) ($limits['requests_per_minute'] ?? 25), 120],
            ["groq:rpd:{$day}", 1, (int) ($limits['requests_per_day'] ?? 900), 172800],
            ["groq:tpm:{$minute}", $estimatedTokens, (int) ($limits['tokens_per_minute'] ?? 7000), 120],
            ["groq:tpd:{$day}", $estimatedTokens, (int) ($limits['tokens_per_day'] ?? 180000), 172800],
        ];

        if ($userId !== null) {
            $windows[] = ["groq:user:{$userId}:{$minute}", 1, (int) ($limits['requests_per_user_per_minute'] ?? 8), 120];
        }

        foreach ($windows as [$key, $cost, $cap, $ttl]) {
            if ($cap > 0 && ((int) Cache::get($key, 0)) + $cost > $cap) {
                return 'rate_limited';
            }
        }

        // Reserve the estimate up front so concurrent requests see the spend.
        foreach ($windows as [$key, $cost, $cap, $ttl]) {
            $this->increment($key, $cost, $ttl);
        }

        return null;
    }

    /**
     * Reconcile the reserved estimate against the provider's reported usage.
     */
    public function recordUsage(int $estimatedTokens, int $actualTokens): void
    {
        $delta = $actualTokens - $estimatedTokens;

        if ($delta === 0) {
            return;
        }

        $minute = now()->format('YmdHi');
        $day = now()->format('Ymd');

        foreach ([["groq:tpm:{$minute}", 120], ["groq:tpd:{$day}", 172800]] as [$key, $ttl]) {
            if ($delta > 0) {
                $this->increment($key, $delta, $ttl);

                continue;
            }

            // Refund the unused reservation, never below zero.
            $current = (int) Cache::get($key, 0);
            Cache::put($key, max(0, $current + $delta), $ttl);
        }
    }

    /**
     * Rough token estimate. Four characters per token is the usual heuristic
     * and only needs to be close enough to keep the reservation honest.
     */
    public function estimateTokens(string $prompt, int $maxResponseTokens): int
    {
        return (int) ceil(mb_strlen($prompt) / 4) + $maxResponseTokens;
    }

    /**
     * Increment a counter, creating it with a TTL when absent.
     */
    protected function increment(string $key, int $amount, int $ttl): void
    {
        if (Cache::add($key, $amount, $ttl)) {
            return;
        }

        Cache::increment($key, $amount);
    }
}
