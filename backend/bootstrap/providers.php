<?php

use App\Providers\AppServiceProvider;
use App\Domain\Inventory\Providers\InventoryServiceProvider;
use App\Domain\Audit\Providers\AuditServiceProvider;

return [
    AppServiceProvider::class,
    InventoryServiceProvider::class,
    AuditServiceProvider::class,
];
