<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ChatPortalMiddleware
{
    public function handle(Request $request, Closure $next, string $portal)
    {
        abort_unless($request->user()?->role === $portal && $request->user()->status === 'active', 403);

        return $next($request);
    }
}
