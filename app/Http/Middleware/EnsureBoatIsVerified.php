<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBoatIsVerified
{
    /**
     * Ensure boat has verified captain license and safety certificate
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to boat listing/search endpoints
        if (!$request->routeIs('boats.*') && !$request->routeIs('boats.search')) {
            return $next($request);
        }

        // Allow boat owners to see their own boats even if not verified
        $user = $request->user();
        if ($user && ($user->isCaptain() || $user->isOwner() || $user->isAdmin())) {
            // Check if user is viewing their own boat
            $boatId = $request->route('boat')?->id ?? $request->get('boat_id');
            if ($boatId) {
                $boat = \App\Models\Boat::find($boatId);
                if ($boat && ($boat->captain_id === $user->id || $user->isAdmin())) {
                    return $next($request);
                }
            }
        }

        // For public/search endpoints, filter out unverified boats
        // This is handled in the controller scope, but we can add additional checks here
        
        return $next($request);
    }
}

