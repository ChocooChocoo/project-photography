<?php

namespace Tests\Feature\Security;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Proves the real CSRF middleware rejects a stale token and accepts a live one.
 *
 * The test runner sets the application environment to testing. The CSRF
 * middleware skips every check in that environment. Each test below switches
 * the environment to production for the one middleware call. A finally block
 * always restores the original environment.
 *
 * The test also reads the shipped client files and checks the token contract.
 */
class CsrfTokenMismatchTest extends TestCase
{
    public function test_a_request_with_a_different_body_token_is_rejected_with_419(): void
    {
        $session = $this->newSession('session-token-value');

        $request = $this->postRequest(['_token' => 'a-different-token']);
        $request->setLaravelSession($session);

        $response = $this->runCsrfMiddleware($request);

        $this->assertSame(419, $response->getStatusCode());
    }

    public function test_a_request_with_a_matching_body_token_passes(): void
    {
        $session = $this->newSession('session-token-value');

        $request = $this->postRequest(['_token' => 'session-token-value']);
        $request->setLaravelSession($session);

        $response = $this->runCsrfMiddleware($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_a_request_with_a_matching_csrf_header_passes(): void
    {
        $session = $this->newSession('session-token-value');

        $request = $this->postRequest();
        $request->setLaravelSession($session);
        $request->headers->set('X-CSRF-TOKEN', 'session-token-value');

        $response = $this->runCsrfMiddleware($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_a_request_with_a_matching_xsrf_header_passes(): void
    {
        $session = $this->newSession('session-token-value');

        // Build the header the same way the framework builds the XSRF-TOKEN
        // cookie. The middleware decrypts the header and removes the prefix.
        $encrypter = app('encrypter');
        $prefix = CookieValuePrefix::create('XSRF-TOKEN', $encrypter->getKey());
        $headerValue = $encrypter->encrypt(
            $prefix.'session-token-value',
            EncryptCookies::serialized('XSRF-TOKEN')
        );

        $request = $this->postRequest();
        $request->setLaravelSession($session);
        $request->headers->set('X-XSRF-TOKEN', $headerValue);

        $response = $this->runCsrfMiddleware($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_session_helper_refreshes_the_form_data_token_and_retries_one_time(): void
    {
        $script = file_get_contents(base_path('public/assets/js/pages/session-token.js'));

        $this->assertIsString($script);

        // The retry puts the live token into a FormData body.
        $this->assertStringContainsString(
            "data.set('_token', value);",
            $script,
            'The helper must refresh the _token field inside a FormData body.'
        );

        // The helper detects a 419 response.
        $this->assertStringContainsString(
            'xhr.status !== 419',
            $script,
            'The helper must detect a 419 response.'
        );

        // The helper marks the request before it sends the request again.
        $this->assertStringContainsString(
            'settings[RETRY_FLAG] = true;',
            $script,
            'The helper must mark the request before the retry.'
        );

        // The helper stops a second retry.
        $this->assertStringContainsString(
            'if (settings[RETRY_FLAG] || settings[OWN_RETRY_FLAG]) {',
            $script,
            'The helper must guard the retry so a request is retried one time only.'
        );
    }

    public function test_login_view_reads_the_token_at_request_time(): void
    {
        $blade = file_get_contents(resource_path('views/auth/login.blade.php'));

        $this->assertIsString($blade);

        $scriptStart = strpos($blade, "@section('scripts')");

        $this->assertNotFalse($scriptStart, 'The login view must have a scripts section.');

        $script = substr($blade, $scriptStart);

        // The submit path uses the shared helper. The helper reads the live
        // token at request time and retries one time after a 419.
        $this->assertStringContainsString(
            'window.PlatinumSession.post',
            $script,
            'The login submit path must use the shared session helper.'
        );

        // The script must not embed a token captured once at render time.
        $this->assertStringNotContainsString(
            'csrf_token',
            $script,
            'The login script must not embed a token captured at render time.'
        );
    }

    /**
     * Run the real CSRF middleware against a crafted request.
     *
     * The application environment is production for the call so the middleware
     * does not skip the check. The original environment is restored in finally.
     */
    private function runCsrfMiddleware(Request $request): Response
    {
        $middleware = app(ValidateCsrfToken::class);

        $next = function (Request $request): Response {
            return response('accepted', 200);
        };

        $originalEnvironment = app()['env'];
        app()['env'] = 'production';

        try {
            return $middleware->handle($request, $next);
        } catch (TokenMismatchException $exception) {
            // The framework turns this exception into the real 419 response.
            return app(ExceptionHandler::class)->render($request, $exception);
        } finally {
            app()['env'] = $originalEnvironment;
        }
    }

    /**
     * Build a POST request to a path that is not exempt from CSRF checks.
     */
    private function postRequest(array $body = []): Request
    {
        return Request::create('/_test/csrf-probe', 'POST', $body);
    }

    /**
     * Build a started session that carries one CSRF token.
     */
    private function newSession(string $token): \Illuminate\Session\Store
    {
        $session = app('session.store');
        $session->start();
        $session->put('_token', $token);

        return $session;
    }
}
