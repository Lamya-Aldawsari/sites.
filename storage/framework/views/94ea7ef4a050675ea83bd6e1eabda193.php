<?php
    $seoService = app(\App\Services\SeoService::class);
    $metaTags = $seoService->getMetaTags($pageType ?? 'home', $identifier ?? null, $model ?? null);
?>

<!-- Primary Meta Tags -->
<title><?php echo e($metaTags['title']); ?></title>
<meta name="title" content="<?php echo e($metaTags['title']); ?>">
<meta name="description" content="<?php echo e($metaTags['description']); ?>">
<?php if(!empty($metaTags['keywords'])): ?>
<meta name="keywords" content="<?php echo e(implode(', ', $metaTags['keywords'])); ?>">
<?php endif; ?>
<meta name="robots" content="<?php echo e($metaTags['robots'] ?? 'index, follow'); ?>">
<meta name="language" content="<?php echo e(app()->getLocale()); ?>">
<meta name="author" content="<?php echo e(config('app.name')); ?>">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="<?php echo e($metaTags['canonical_url'] ?? url()->current()); ?>">
<meta property="og:title" content="<?php echo e($metaTags['og_title']); ?>">
<meta property="og:description" content="<?php echo e($metaTags['og_description']); ?>">
<?php if($metaTags['og_image']): ?>
<meta property="og:image" content="<?php echo e($metaTags['og_image']); ?>">
<?php endif; ?>

<!-- Twitter -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:url" content="<?php echo e($metaTags['canonical_url'] ?? url()->current()); ?>">
<meta property="twitter:title" content="<?php echo e($metaTags['og_title']); ?>">
<meta property="twitter:description" content="<?php echo e($metaTags['og_description']); ?>">
<?php if($metaTags['og_image']): ?>
<meta property="twitter:image" content="<?php echo e($metaTags['og_image']); ?>">
<?php endif; ?>

<!-- Canonical URL -->
<?php if($metaTags['canonical_url']): ?>
<link rel="canonical" href="<?php echo e($metaTags['canonical_url']); ?>">
<?php endif; ?>

<!-- Structured Data (JSON-LD) -->
<?php if(!empty($metaTags['structured_data'])): ?>
<script type="application/ld+json">
<?php echo json_encode($metaTags['structured_data'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>

</script>
<?php endif; ?>

<?php /**PATH C:\iboat-laravel\resources\views/components/meta-tags.blade.php ENDPATH**/ ?>