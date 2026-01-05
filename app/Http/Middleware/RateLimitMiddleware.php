<?php

namespace App\Http\Middleware;

use App\Services\CacheService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    protected $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decaySeconds = 60): Response
    {
        $key = 'rate_limit:' . $request->ip() . ':' . $request->path();
        
        if (!$this->cacheService->rateLimit($key, $maxAttempts, $decaySeconds)) {
            return response()->json([
                'message' => 'Too many requests. Please try again later.'
            ], 429);
        }

        return $next($request);
    }
}

