<?php

use App\Http\Controllers\AI\ChatController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // AI Chat Pages
    Route::get('/ai/chat', [ChatController::class, 'index'])->name('ai.chat.index');
    Route::get('/ai/chat/{conversationId}', [ChatController::class, 'show'])->name('ai.chat.show');
    
    // Optional: AI Reports/Analytics Pages
    Route::get('/ai/reports', [ChatController::class, 'reports'])->name('ai.reports');
    Route::get('/ai/models', [ChatController::class, 'models'])->name('ai.models');
});
