<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\WhatsAppController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SalesController;
use App\Http\Controllers\Api\BillingController;

// Webhook público para Evolution API (sin autenticación)
Route::any('/webhooks/whatsapp', [WebhookController::class, 'whatsapp'])
    ->middleware('throttle:60,1');

// Webhook de MercadoPago
Route::post('/billing/webhook', [BillingController::class, 'webhook']);
Route::any('/webhook-test', function(\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Log::info('Webhook test', [
        'method' => $request->method(),
        'path' => $request->path(),
        'headers' => $request->headers->all(),
        'body' => $request->all(),
    ]);
    return response()->json(['received' => true, 'data' => $request->all()]);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

// Planes público (para ver antes de registrarse)
Route::get('/plans', [TenantController::class, 'plans']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tenant', [TenantController::class, 'show']);
    Route::put('/tenant', [TenantController::class, 'update']);
    Route::get('/tenant/onboarding', [TenantController::class, 'onboardingStatus']);
    Route::put('/tenant/onboarding', [TenantController::class, 'updateOnboarding']);

    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/{contact}', [ConversationController::class, 'show']);
    Route::get('/conversations/{contact}/messages', [ConversationController::class, 'messages']);

    Route::get('/dashboard/recent-conversations', [DashboardController::class, 'recentConversations']);
    Route::get('/dashboard/metrics', [DashboardController::class, 'metrics']);

    Route::get('/whatsapp/status', [WhatsAppController::class, 'status']);
    Route::post('/whatsapp/connect', [WhatsAppController::class, 'connect']);
    Route::post('/whatsapp/disconnect', [WhatsAppController::class, 'disconnect']);
    Route::get('/whatsapp/qr', [WhatsAppController::class, 'qr']);
    Route::get('/whatsapp/check-messages', [WhatsAppController::class, 'checkMessages']);

    Route::get('/catalog', [CatalogController::class, 'getCurrent']);
    Route::get('/catalog/template', [CatalogController::class, 'getTemplate']);
    Route::post('/catalog/upload', [CatalogController::class, 'upload']);
    Route::post('/catalog/items', [CatalogController::class, 'storeItem']);
    Route::get('/catalog/{catalogId}/items', [CatalogController::class, 'getItems']);
    Route::put('/catalog/items/{item}', [CatalogController::class, 'updateItem']);
    Route::delete('/catalog/{catalogId}', [CatalogController::class, 'destroy']);

    Route::get('/sales', [SalesController::class, 'index']);
    Route::patch('/sales/{sale}/status', [SalesController::class, 'updateStatus']);

    Route::post('/billing/subscription', [BillingController::class, 'createSubscription']);
    Route::get('/billing/status', [BillingController::class, 'status']);
});
