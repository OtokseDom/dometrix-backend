<?php

use App\Http\Controllers\API\V1\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\OrganizationController;
use App\Http\Controllers\API\V1\UserController;
use App\Http\Controllers\API\V1\RoleController;
use App\Http\Controllers\API\V1\UnitsController;

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
        Route::apiResource('units', UnitsController::class);
    });
});
