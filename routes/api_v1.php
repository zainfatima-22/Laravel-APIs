<?php

use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\UsersController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::apiResource('ticket', TicketController::class);
Route::apiResource('users', UsersController::class);

Route::get('ticket/users/{user_id}', [TicketController::class, 'userTicketsById']);

// Route for PUT (Edit) and PATCH (Update)
Route::put('ticket/{ticket}', [TicketController::class, 'edit']);
Route::middleware('auth:sanctum')->patch('ticket/{ticket}', [TicketController::class, 'update']);
Route::middleware('auth:sanctum')->post('/ticket', [TicketController::class, 'store']);


Route::middleware('auth:sanctum')->group(function () {
    // Custom Route 2: Show tickets belonging to the currently authenticated user
    // Endpoint: GET /api/v1/my-tickets
    Route::get('my-tickets', [TicketController::class, 'userTickets']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
