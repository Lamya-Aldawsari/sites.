<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" dir="<?php echo e(app()->getLocale() === 'ar' ? 'rtl' : 'ltr'); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        <?php echo $__env->make('components.meta-tags', [
            'pageType' => $pageType ?? 'home',
            'identifier' => $identifier ?? null,
            'model' => $model ?? null
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        
        <!-- Preconnect to external domains for performance -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="dns-prefetch" href="https://api.stripe.com">
        
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.jsx']); ?>
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>

<?php /**PATH C:\iboat-laravel\resources\views/app.blade.php ENDPATH**/ ?>