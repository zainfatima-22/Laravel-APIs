<?php

use App\Http\Controllers\Api\APIController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [APIController::class,'login']);
Route::get('/login', [APIController::class,'login']);
Route::middleware('auth:sanctum')->post('/logout', [APIController::class,'logout']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')
    ->prefix('v1') // Adds the /v1 prefix on top of the existing /api prefix
    ->group(base_path('routes/api_v1.php'));

