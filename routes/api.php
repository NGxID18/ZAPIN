<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Placeholder API routes for ZAPIN. Ready for new hybrid sync architecture.
*/

Route::get('/ping', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'ZAPIN API ready',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::post('/sheets/webhook-update', [\App\Http\Controllers\AlkesController::class, 'handleSheetWebhookUpdate'])
    ->middleware('throttle:60,1');

