<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Keeps the session alive when the user clicks "extend" in the idle-timeout warning.
 */
class SessionController extends Controller
{
    public function ping(Request $request): JsonResponse
    {
        $request->session()->put('last_ping_at', now()->timestamp);

        return response()->json([
            'ok' => true,
            'expires_in' => (int) config('session.lifetime') * 60,
        ]);
    }
}
