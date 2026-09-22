<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SessionTokenController extends Controller
{
    /**
     * Return the live CSRF token for the current session.
     */
    public function token(): JsonResponse
    {
        return response()->json(['token' => csrf_token()]);
    }
}
