<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify-mfa', [AuthController::class, 'verifyMfa']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:view inventory|view reports|read only access');

    Route::apiResource('users', UserController::class)
        ->middleware('permission:manage users');

    Route::get('roles', [RoleController::class, 'index'])
        ->middleware('permission:manage roles|view audit logs');
    Route::post('roles', [RoleController::class, 'store'])
        ->middleware('permission:manage roles');
    Route::put('roles/{role}', [RoleController::class, 'update'])
        ->middleware('permission:manage roles');

    Route::get('products', [ProductController::class, 'index'])
        ->middleware('permission:view inventory|read only access|create item');
    Route::post('products', [ProductController::class, 'store'])
        ->middleware('permission:create item');
    Route::get('products/{product}', [ProductController::class, 'show'])
        ->middleware('permission:view inventory|read only access|create item');
    Route::put('products/{product}', [ProductController::class, 'update'])
        ->middleware('permission:update item');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])
        ->middleware('permission:delete item');

    Route::get('categories', [CategoryController::class, 'index'])
        ->middleware('permission:manage categories|view inventory|read only access');
    Route::post('categories', [CategoryController::class, 'store'])
        ->middleware('permission:manage categories');
    Route::put('categories/{category}', [CategoryController::class, 'update'])
        ->middleware('permission:manage categories');

    Route::get('suppliers', [SupplierController::class, 'index'])
        ->middleware('permission:manage suppliers|view inventory|read only access');
    Route::post('suppliers', [SupplierController::class, 'store'])
        ->middleware('permission:manage suppliers');
    Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])
        ->middleware('permission:manage suppliers');

    Route::prefix('inventory')->group(function () {
        Route::post('stock-in', [InventoryController::class, 'stockIn'])
            ->middleware('permission:receive stocks');
        Route::post('stock-out', [InventoryController::class, 'stockOut'])
            ->middleware('permission:release stocks');
        Route::post('adjustment', [InventoryController::class, 'adjustment'])
            ->middleware('permission:manage stock adjustments');
        Route::get('history', [InventoryController::class, 'history'])
            ->middleware('permission:view transactions|view inventory|create inventory transactions');
    });

    Route::prefix('reports')->group(function () {
        Route::get('inventory', [ReportController::class, 'inventory'])
            ->middleware('permission:view reports|generate reports|read only access');
        Route::get('movements', [ReportController::class, 'movements'])
            ->middleware('permission:view reports|view transactions|generate reports');
        Route::get('audit', [ReportController::class, 'audit'])
            ->middleware('permission:view audit logs|generate reports');
    });
});
