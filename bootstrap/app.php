<?php

use App\Http\Middleware\NoStoreSessionResponses;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // W2 owns this route file. Load it only when it exists so one
            // missing workstream cannot stop the application from booting.
            if (file_exists(base_path('routes/session.php'))) {
                Route::middleware('web')->group(base_path('routes/session.php'));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Add no-store headers to authenticated HTML pages. Assets and JSON
        // responses keep their own cache behavior.
        $middleware->web(append: [
            NoStoreSessionResponses::class,
            \App\Http\Middleware\HideInternalErrorDetails::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhook/paymongo',
            'webhook/stripe',
            'webhook/stripe/subscriptions',
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'client' => \App\Http\Middleware\ClientMiddleware::class,
            'owner' => \App\Http\Middleware\OwnerMiddleware::class,
            'freelancer' => \App\Http\Middleware\FreelancerMiddleware::class,
            'studio.photographer' => \App\Http\Middleware\StudioPhotographerMiddleware::class,
            'studio.hr' => \App\Http\Middleware\StudioHRMiddleware::class,
            'studio.finance' => \App\Http\Middleware\StudioFinanceMiddleware::class,
            'check.studio.limit' => \App\Http\Middleware\CheckStudioRegistrationLimit::class,
            'permission' => \App\Http\Middleware\CheckPermissionMiddleware::class,
            'subscription.access' => \App\Http\Middleware\EnforceStudioSubscriptionAccess::class,
            'permit.verified' => \App\Http\Middleware\PermitVerificationMiddleware::class,
            'password.changed' => \App\Http\Middleware\EnsurePasswordChangedMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A stale CSRF token reaches here as a 419 HttpException. Screens show
        // the JSON message as is, so give it in plain language.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 419 && $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your session expired. Reload the page and try again.',
                ], 419);
            }
        });
    })->create();
