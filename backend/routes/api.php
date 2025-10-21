<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// Authentication Routes
Route::prefix('auth')->group(function () {
    // Public routes (no authentication required)
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    
    // Protected routes (authentication required)
    Route::middleware('auth:api')->group(function () {
        Route::post('/register', [AuthController::class, 'register']); // Admin only
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
    });
});