<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiLoginRequest;
use App\Http\Requests\Api\ApiRegisterRequest;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class APIController extends Controller
{
    use ApiResponses, AuthorizesRequests;
    public function register(ApiRegisterRequest $request): JsonResponse
    {
        $this->authorize('register', User::class);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $token = $user->createToken("API Token for {$user->email}");

        return $this->created('Registration successful', [
            'user' => $user->only('id', 'name', 'email'),
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => null,
        ]);
    }

    public function login(ApiLoginRequest $request): JsonResponse
    {
        $this->authorize('login', User::class);

        if (!Auth::guard('web')->attempt($request->only('email', 'password'))) {
            return $this->unauthorized('Invalid credentials.');
        }

        $user = Auth::guard('web')->user();
        $user->tokens()->delete();
        $token = $user->createToken("API Token for {$user->email}");

        return $this->ok('Authenticated successfully', [
            'user' => $user->only('id', 'name', 'email'),
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => null,
        ]);
    }
    
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->authorize('logout', User::class);

        $user?->tokens()->delete();

        return $this->ok('Logged out successfully');
    }
}
