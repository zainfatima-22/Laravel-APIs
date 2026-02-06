<?php

use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\TicketOrionController;
use App\Http\Controllers\Api\V1\UsersController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Orion\Facades\Orion;

Route::apiResource('tickets', TicketController::class);
Route::apiResource('users', UsersController::class);
Route::get('user', function (Request $request) {
        return $request->user();
    });
Route::prefix('users/{user}')->group(function () {
        Route::get('tickets', [TicketController::class, 'getTicketsByUser']); 
        Route::get('tickets/{ticket}', [TicketController::class, 'getSpecificTicketByUser']); 
}); 
Route::middleware('auth:sanctum')
    ->group(function () {
        Orion::resource('ticket', TicketController::class);
    });

