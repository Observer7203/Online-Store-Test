<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Models\Attribute;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::post('/attributes', [AttributeController::class, 'store'])->can('manage', Attribute::class);
    Route::delete('/attributes/{id}', [AttributeController::class, 'destroy'])->can('manage', Attribute::class);
    Route::get('/orders', [OrderController::class, 'index']);
});

    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);
    Route::get('/categories/tree', [CategoryController::class, 'tree']);
    Route::get('/attributes', [AttributeController::class, 'index']);
});
