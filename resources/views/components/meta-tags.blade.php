@php
    $seoService = app(\App\Services\SeoService::class);
    $metaTags = $seoService->getMetaTags($pageType ?? 'home', $identifier ?? null, $model ?? null);
@endphp

<!-- Primary Meta Tags -->
<title>{{ $metaTags['title'] }}</title>
<meta name="title" content="{{ $metaTags['title'] }}">
<meta name="description" content="{{ $metaTags['description'] }}">
@if(!empty($metaTags['keywords']))
<meta name="keywords" content="{{ implode(', ', $metaTags['keywords']) }}">
@endif
<meta name="robots" content="{{ $metaTags['robots'] ?? 'index, follow' }}">
<meta name="language" content="{{ app()->getLocale() }}">
<meta name="author" content="{{ config('app.name') }}">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="{{ $metaTags['canonical_url'] ?? url()->current() }}">
<meta property="og:title" content="{{ $metaTags['og_title'] }}">
<meta property="og:description" content="{{ $metaTags['og_description'] }}">
@if($metaTags['og_image'])
<meta property="og:image" content="{{ $metaTags['og_image'] }}">
@endif

<!-- Twitter -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:url" content="{{ $metaTags['canonical_url'] ?? url()->current() }}">
<meta property="twitter:title" content="{{ $metaTags['og_title'] }}">
<meta property="twitter:description" content="{{ $metaTags['og_description'] }}">
@if($metaTags['og_image'])
<meta property="twitter:image" content="{{ $metaTags['og_image'] }}">
@endif

<!-- Canonical URL -->
@if($metaTags['canonical_url'])
<link rel="canonical" href="{{ $metaTags['canonical_url'] }}">
@endif

<!-- Structured Data (JSON-LD) -->
@if(!empty($metaTags['structured_data']))
<script type="application/ld+json">
{!! json_encode($metaTags['structured_data'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endif

