<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TagController;
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


// Category Management Routes (Admin Only)
Route::middleware(['auth:api', 'role:ADMIN'])->prefix('categories')->group(function () {
    Route::put('/reorder', [CategoryController::class, 'reorder']);
    
    // CRUD operations
    Route::get('/', [CategoryController::class, 'index']);
    Route::post('/', [CategoryController::class, 'store']);
    Route::get('/{id}', [CategoryController::class, 'show']);
    Route::put('/{id}', [CategoryController::class, 'update']);
    Route::delete('/{id}', [CategoryController::class, 'destroy']);

    // TODO: Tambahkan route untuk statistics dan toggle status jika Anda membuatnya di CategoryController
    // Route::get('/statistics', [CategoryController::class, 'statistics']);
    // Route::post('/{id}/toggle-status', [CategoryController::class, 'toggleStatus']);
});

// Tag Management Routes (Admin & Editor)
Route::middleware(['auth:api', 'role:ADMIN,EDITOR'])->prefix('tags')->group(function () {
    Route::get('/statistics', [TagController::class, 'statistics']);
    Route::post('/find-or-create', [TagController::class, 'findOrCreate']);
    Route::post('/bulk-delete', [TagController::class, 'bulkDelete']);
    
    // CRUD operations
    Route::get('/', [TagController::class, 'index']);
    Route::post('/', [TagController::class, 'store']);
    Route::get('/{id}', [TagController::class, 'show']);
    Route::put('/{id}', [TagController::class, 'update']);
    Route::delete('/{id}', [TagController::class, 'destroy']);
});