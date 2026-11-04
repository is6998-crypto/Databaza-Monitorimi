<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff-only areas outside the Filament panel (monitor PWA): active staff with 2FA set up.
 * Users without 2FA are sent to the panel, which forces the setup.
 */
class EnsureStaffWithMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->is_active || ! $user->isStaff()) {
            abort(403);
        }

        if (! $user->hasMultiFactorAuthentication()) {
            return $request->expectsJson()
                ? response()->json(['message' => __('monitor.mfa_required')], 403)
                : redirect('/admin');
        }

        return $next($request);
    }
}
