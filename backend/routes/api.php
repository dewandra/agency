<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;

// Authentication Routes
Route::prefix('auth')->group(function () {
    // Public routes (no authentication required)
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    
    // Protected routes (authentication required)
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
    });
});

// User Management Routes (Admin Only)
Route::middleware(['auth:api', 'role:ADMIN'])->prefix('users')->group(function () {
    // Statistics (must be before {id} route)
    Route::get('/statistics', [UserController::class, 'statistics']);
    
    // Bulk operations
    Route::post('/bulk-delete', [UserController::class, 'bulkDelete']);
    Route::post('/bulk-update-status', [UserController::class, 'bulkUpdateStatus']);
    
    // CRUD operations
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
    
    // Additional operations
    Route::post('/{id}/restore', [UserController::class, 'restore']);
    Route::delete('/{id}/force', [UserController::class, 'forceDestroy']);
    Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
    Route::put('/{id}/role', [UserController::class, 'changeRole']);
});