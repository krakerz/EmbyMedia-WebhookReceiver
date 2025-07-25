<?php

use App\Http\Controllers\EmbyWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EmbyWebhookController::class, 'index'])->name('webhooks.index');
Route::get('/webhook/{webhook}', [EmbyWebhookController::class, 'show'])->name('webhooks.show');

// Webhook endpoint (should be accessible without CSRF protection)
Route::post('/emby/webhook', [EmbyWebhookController::class, 'handleWebhook'])->name('emby.webhook');
