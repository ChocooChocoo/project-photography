<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    /**
     * A record the user opened was removed or is not theirs. Say so with a 404
     * instead of letting a catch-all turn it into a 500 "Failed to load".
     */
    protected function recordNotFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'This record was not found. It may have been removed.',
        ], 404);
    }
}
