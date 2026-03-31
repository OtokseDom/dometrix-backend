<?php

use App\Http\Controllers\API\V1\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\OrganizationController;
use App\Http\Controllers\API\V1\UserController;
use App\Http\Controllers\API\V1\RoleController;
use App\Http\Controllers\API\V1\OrganizationUserController;
use App\Http\Controllers\API\V1\UnitsController;
use App\Http\Controllers\API\V1\CurrenciesController;
use App\Http\Controllers\API\V1\ManufacturingCostController;
use App\Http\Controllers\API\V1\MaterialController;
use App\Http\Controllers\API\V1\MaterialPriceController;
use App\Http\Controllers\API\V1\ProductController;
use App\Http\Controllers\API\V1\BomController;
use App\Http\Controllers\API\V1\BomItemController;
use App\Http\Controllers\API\V1\CategoryController;
use App\Http\Controllers\API\V1\TaxController;
use App\Http\Controllers\API\V1\WarehouseController;

Route::prefix('v1')->group(function () {

    // AUTHENTICATION ROUTES
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('password-reset', [AuthController::class, 'passwordReset']);
        Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);
    });
    // MAIN API ROUTES (PROTECTED)
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('organizations', OrganizationController::class);
        Route::apiResource('users', UserController::class);
        Route::apiResource('roles', RoleController::class);
        Route::apiResource('organization-users', OrganizationUserController::class);
        Route::apiResource('units', UnitsController::class);
        Route::apiResource('currencies', CurrenciesController::class);
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('taxes', TaxController::class);
        Route::apiResource('warehouses', WarehouseController::class);

        // MANUFACTURING CRUD ROUTES
        Route::prefix('manufacturing')->group(function () {
            Route::apiResource('materials', MaterialController::class);
            Route::apiResource('material-prices', MaterialPriceController::class);
            Route::apiResource('products', ProductController::class);
            Route::apiResource('boms', BomController::class);
            Route::apiResource('bom-items', BomItemController::class);

            // COSTING & ANALYSIS ROUTES
            Route::post('material-cost', [ManufacturingCostController::class, 'calculateMaterialCost']);
            Route::get('materials/{id}/price-history', [ManufacturingCostController::class, 'getMaterialPriceHistory']);

            Route::post('bom-cost', [ManufacturingCostController::class, 'calculateBomCost']);

            Route::post('product-cost', [ManufacturingCostController::class, 'calculateProductCost']);
            Route::get('products/{id}/cost-summary', [ManufacturingCostController::class, 'getProductCostSummary']);
        });
    });
});
