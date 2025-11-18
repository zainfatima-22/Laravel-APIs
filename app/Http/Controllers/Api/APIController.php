<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiLoginRequest;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class APIController extends Controller
{
    use ApiResponses;

    // LOGIN
    public function login(ApiLoginRequest $request)
    {
        $request->validated($request->all());

        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->error('Invalid Credentials', 401);
        }

        $user = User::firstWhere('email', $request->email);

        $tokenResult = $user->createToken('API Token For ' . $user->email);

        return $this->ok('authenticated', [
            'token' => $tokenResult->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => null,
        ]);
    }

    // LOGOUT
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->ok('Logout Successfully');
    }
}
