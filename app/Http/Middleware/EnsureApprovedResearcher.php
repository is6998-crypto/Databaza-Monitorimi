<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Researcher API: only approved researchers who accepted the current data-use agreement,
 * using a token with the "research:read" ability.
 */
class EnsureApprovedResearcher
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isApprovedResearcher()) {
            return response()->json(['message' => __('research.api.not_approved')], 403);
        }

        if (! $user->tokenCan('research:read')) {
            return response()->json(['message' => __('research.api.missing_ability')], 403);
        }

        return $next($request);
    }
}
