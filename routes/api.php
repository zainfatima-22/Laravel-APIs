<?php

use App\Http\Controllers\Api\APIController;
use App\Http\Resources\V1\TicketResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DebugTokenController;


Route::post('/login', [APIController::class,'login']);
Route::post('/register', [APIController::class,'register']);
Route::middleware('auth:sanctum')->post('/logout', [APIController::class,'logout']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')
    ->prefix('v1') 
    ->group(base_path('routes/api_v1.php'));

Route::post('/debug/token', [DebugTokenController::class, 'getToken']);
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

