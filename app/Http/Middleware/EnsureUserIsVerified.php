<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->isCaptain() || $user->isVendor()) && !$user->is_verified) {
            return response()->json([
                'message' => 'Your account needs to be verified before you can perform this action.'
            ], 403);
        }

        return $next($request);
    }
}

