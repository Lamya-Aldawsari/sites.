<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        @include('components.meta-tags', [
            'pageType' => $pageType ?? 'home',
            'identifier' => $identifier ?? null,
            'model' => $model ?? null
        ])
        
        <!-- Preconnect to external domains for performance -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="dns-prefetch" href="https://api.stripe.com">
        
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>

