<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\FreelancerMiddleware;
use App\Http\Middleware\OwnerMiddleware;
use App\Http\Middleware\StudioFinanceMiddleware;
use App\Http\Middleware\StudioHRMiddleware;
use App\Http\Middleware\StudioPhotographerMiddleware;
use App\Models\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

/**
 * Client test C-03: Dashboard "Export" returned a 500 for owner, HR, finance
 * and photographer, because the portal middleware called ->header() on a
 * StreamedResponse. File downloads (BinaryFileResponse) had the same problem.
 */
class PortalMiddlewareDownloadTest extends TestCase
{
    public static function portals(): array
    {
        return [
            'owner' => [OwnerMiddleware::class, 'owner'],
            'hr' => [StudioHRMiddleware::class, 'studio-hr'],
            'finance' => [StudioFinanceMiddleware::class, 'studio-finance'],
            'photographer' => [StudioPhotographerMiddleware::class, 'studio-photographer'],
            'freelancer' => [FreelancerMiddleware::class, 'freelancer'],
        ];
    }

    /**
     * @dataProvider portals
     */
    public function test_downloads_pass_through_the_portal_middleware_with_no_cache_headers(string $middleware, string $role): void
    {
        Auth::setUser(new UserModel(['role' => $role, 'status' => 'active']));
        $file = tempnam(sys_get_temp_dir(), 'dl');
        file_put_contents($file, 'a,b');

        foreach ([new StreamedResponse(fn () => print 'a,b'), new BinaryFileResponse($file)] as $download) {
            $response = app($middleware)->handle(Request::create('/export'), fn () => $download);

            $this->assertSame($download, $response);
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $this->assertSame('no-cache', $response->headers->get('Pragma'));
        }

        unlink($file);
    }
}
