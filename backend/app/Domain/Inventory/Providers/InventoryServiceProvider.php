<?php

namespace App\Domain\Inventory\Providers;

use App\Domain\Inventory\Services\InventoryMovementService;
use App\Domain\Inventory\Services\InventoryBalanceService;
use App\Domain\Inventory\Services\InventoryCostLayerService;
use App\Domain\Inventory\Services\InventoryTransactionService;
use App\Domain\Inventory\Services\InventoryReportingService;
use App\Domain\Audit\Services\AuditTrailService;
use Illuminate\Support\ServiceProvider;

class InventoryServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        $this->registerCoreServices();
        $this->registerAggregateServices();
    }

    /**
     * Boot services
     */
    public function boot(): void
    {
        // Any initialization logic here
    }

    /**
     * Register core inventory services
     */
    private function registerCoreServices(): void
    {
        $this->app->singleton(InventoryBalanceService::class, function ($app) {
            return new InventoryBalanceService();
        });

        $this->app->singleton(InventoryCostLayerService::class, function ($app) {
            return new InventoryCostLayerService();
        });

        $this->app->singleton(AuditTrailService::class, function ($app) {
            return new AuditTrailService();
        });
    }

    /**
     * Register aggregate services that depend on core services
     */
    private function registerAggregateServices(): void
    {
        $this->app->singleton(InventoryMovementService::class, function ($app) {
            return new InventoryMovementService(
                balanceService: $app->make(InventoryBalanceService::class),
                costLayerService: $app->make(InventoryCostLayerService::class),
                auditService: $app->make(AuditTrailService::class),
            );
        });

        $this->app->singleton(InventoryTransactionService::class, function ($app) {
            return new InventoryTransactionService(
                movementService: $app->make(InventoryMovementService::class),
                balanceService: $app->make(InventoryBalanceService::class),
                costLayerService: $app->make(InventoryCostLayerService::class),
                auditService: $app->make(AuditTrailService::class),
            );
        });

        $this->app->singleton(InventoryReportingService::class, function ($app) {
            return new InventoryReportingService();
        });
    }
}
