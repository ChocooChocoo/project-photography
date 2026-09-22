<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stops the browser from caching an HTML page.
 *
 * A cached page carries the CSRF token that was rendered with it. If the
 * browser serves that page again from the cache, the form sends a token that
 * no longer matches the session. The request then fails with a 419 error.
 *
 * A guest page holds a token too, so it also must not cache.
 */
class NoStoreSessionResponses
{
    /**
     * Add no-store headers to HTML responses. Other response types keep their own.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Leave assets, downloads, JSON, and redirects with their own headers.
        if (! $this->isHtmlResponse($response)) {
            return $response;
        }

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    /**
     * Report whether the response body is an HTML page.
     *
     * A redirect keeps its own headers. Symfony marks a redirect as text/html,
     * so the status code is checked first.
     */
    private function isHtmlResponse(Response $response): bool
    {
        if ($response->isRedirection()) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type');

        return str_contains(strtolower($contentType), 'text/html');
    }
}
