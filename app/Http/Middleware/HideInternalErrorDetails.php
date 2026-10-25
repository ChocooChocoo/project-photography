<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Many catch blocks return $e->getMessage() in their JSON error response, which
 * can show SQL, the database name and the host. With debug off, keep those
 * details in the log and send the browser a plain message instead.
 */
class HideInternalErrorDetails
{
    public const GENERIC_MESSAGE = 'Something went wrong. Please try again.';

    private const DETAIL_KEYS = ['error', 'exception', 'file', 'line', 'trace'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (config('app.debug') || ! $response instanceof JsonResponse || $response->getStatusCode() < 400) {
            return $response;
        }

        $original = $response->getData(true);
        if (! is_array($original)) {
            return $response;
        }

        $clean = $original;

        if ($response->getStatusCode() >= 500) {
            foreach (self::DETAIL_KEYS as $key) {
                unset($clean[$key]);
            }
            // ponytail: a 500 message with a colon is almost always "Failed to …: <exception>".
            // Plain messages such as "Failed to create package. Please try again." have none.
            if (! isset($clean['message']) || ! is_string($clean['message']) || str_contains($clean['message'], ':')) {
                $clean['message'] = self::GENERIC_MESSAGE;
            }
        }

        // Any status: database, class and file details never reach the browser.
        array_walk_recursive($clean, function (&$value) {
            if (is_string($value) && self::looksInternal($value)) {
                $value = self::GENERIC_MESSAGE;
            }
        });

        if ($clean !== $original) {
            Log::error('Hid internal error details from a JSON response', [
                'url' => $request->fullUrl(),
                'status' => $response->getStatusCode(),
                'user_id' => $request->user()?->id,
                'response' => $original,
            ]);
            $response->setData($clean);
        }

        return $response;
    }

    private static function looksInternal(string $value): bool
    {
        return (bool) preg_match('/SQLSTATE|\(Connection: |Stack trace|\.php\b|App\\\\|Illuminate\\\\|vendor[\/\\\\]/', $value);
    }
}
