<?php

use App\Http\Controllers\Api\APIController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Resources\V1\TicketResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DebugTokenController;
use App\Http\Middleware\SyncPermissionsOnToken;


Route::post('/login', [APIController::class,'login']);
Route::post('/register', [APIController::class,'register']);
Route::middleware(['auth:sanctum', SyncPermissionsOnToken::class])->post('/logout', [APIController::class,'logout']);
Route::post('/tickets/batch-emails', [TicketController::class, 'sendBatchEmails']);

Route::middleware(['auth:sanctum', SyncPermissionsOnToken::class])
    ->prefix('v1') 
    ->group(base_path('routes/api_v1.php'));

Route::get('/preview-email', function () {
    $ticket = \App\Models\Ticket::first() ?? \App\Models\Ticket::factory()->make(['title' => 'Preview','description'=>'','status'=>'open']);
    $user = \App\Models\User::first() ?? \App\Models\User::factory()->make(['email'=>'your@email.com']);
    return (new App\Notifications\TicketCreatedNotification($ticket))->toMail($user)->render();
});
Route::get('/v1/public/tickets/{id}', function ($id) {
    return TicketResource::make(
        \App\Models\Ticket::findOrFail($id)
    );
});
Route::middleware(['auth:api', SyncPermissionsOnToken::class])->get('/user', function (Request $request) {
    return $request->user();
});
Route::middleware(['auth:api', SyncPermissionsOnToken::class])->apiResource('ticketss', TicketController::class); 