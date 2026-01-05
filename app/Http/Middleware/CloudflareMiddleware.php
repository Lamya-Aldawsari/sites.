<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CloudflareMiddleware
{
    /**
     * Handle an incoming request and add Cloudflare headers
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add Cloudflare security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Cloudflare cache headers
        if ($request->isMethod('GET')) {
            // Cache static assets for 1 year
            if ($request->is('*.css') || $request->is('*.js') || $request->is('*.jpg') || $request->is('*.png') || $request->is('*.gif')) {
                $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
            }
            // Cache API responses for 5 minutes
            elseif ($request->is('api/*')) {
                $response->headers->set('Cache-Control', 'public, max-age=300');
            }
            // Cache HTML pages for 1 hour
            else {
                $response->headers->set('Cache-Control', 'public, max-age=3600');
            }
        }

        // Add Cloudflare IP headers if behind Cloudflare
        if ($request->header('CF-Connecting-IP')) {
            $request->server->set('REMOTE_ADDR', $request->header('CF-Connecting-IP'));
        }

        return $response;
    }
}

