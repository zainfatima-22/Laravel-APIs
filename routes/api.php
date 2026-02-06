<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Resources\V1\TicketResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\SyncPermissionsOnToken;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/v1/public/tickets/{id}', function ($id) {
    return TicketResource::make(
        \App\Models\Ticket::findOrFail($id)
    );
});
Route::middleware(['auth:sanctum', SyncPermissionsOnToken::class])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/tickets/batch-emails', [TicketController::class, 'sendBatchEmails']);
    Route::prefix('v1')->group(base_path('routes/api_v1.php'));
    Route::apiResource('tickets', TicketController::class);
});
