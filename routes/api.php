<?php

use Illuminate\Support\Facades\Route;


Route::get('/ping', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'ZAPIN API ready',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::get('/sheets/export-data', [\App\Http\Controllers\AlkesController::class, 'exportSheetData'])
    ->middleware('throttle:60,1');

Route::post('/sheets/webhook-update', [\App\Http\Controllers\AlkesController::class, 'handleSheetWebhookUpdate'])
    ->middleware('throttle:60,1');

