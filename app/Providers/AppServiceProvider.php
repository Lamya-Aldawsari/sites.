<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Foundation\MaintenanceModeManager;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MaintenanceMode::class, function ($app) {
            return $app->make(MaintenanceModeManager::class)->driver();
        });
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}

