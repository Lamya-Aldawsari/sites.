<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('Accept-Language', 'en');
        $locale = $request->get('locale', Session::get('locale', $locale));
        
        // Validate locale
        if (!in_array($locale, config('app.available_locales', ['en', 'ar']))) {
            $locale = config('app.fallback_locale', 'en');
        }

        App::setLocale($locale);
        Session::put('locale', $locale);

        return $next($request);
    }
}

