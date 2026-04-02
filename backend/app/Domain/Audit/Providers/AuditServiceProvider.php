<?php

namespace App\Domain\Audit\Providers;

use App\Domain\Audit\Services\AuditTrailService;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        $this->app->singleton(AuditTrailService::class, function ($app) {
            return new AuditTrailService();
        });
    }

    /**
     * Boot services
     */
    public function boot(): void
    {
        // Any initialization logic here
    }
}
