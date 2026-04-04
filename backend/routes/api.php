<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\WhatsAppController;

Route::post('/webhooks/whatsapp', [WebhookController::class, 'whatsapp']);

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tenant', [TenantController::class, 'show']);
    Route::put('/tenant', [TenantController::class, 'update']);
    Route::get('/tenant/onboarding-status', [TenantController::class, 'onboardingStatus']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    Route::post('/products/import', [ProductController::class, 'import']);

    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/{contact}', [ConversationController::class, 'show']);
    Route::get('/conversations/{contact}/messages', [ConversationController::class, 'messages']);

    Route::get('/whatsapp/status', [WhatsAppController::class, 'status']);
    Route::post('/whatsapp/connect', [WhatsAppController::class, 'connect']);
    Route::post('/whatsapp/disconnect', [WhatsAppController::class, 'disconnect']);
    Route::get('/whatsapp/qr', [WhatsAppController::class, 'qr']);
});
