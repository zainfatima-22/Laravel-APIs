<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;

class DebugTokenController extends Controller
{
    public function getToken(Request $request)
    {
        try {
            $response = Http::asForm()->post(url('/oauth/token'), [
                'grant_type' => 'password',
                'client_id' => env('PASSPORT_PASSWORD_CLIENT_ID'),
                'client_secret' => env('PASSPORT_PASSWORD_CLIENT_SECRET'),
                'username' => $request->username,
                'password' => $request->password,
                'scope' => '*',
            ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'error_details' => $response->json(),
                ], $response->status());
            }

            return response()->json([
                'success' => true,
                'data' => $response->json(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'exception_message' => $e->getMessage(),
            ], 500);
        }
    }
}
