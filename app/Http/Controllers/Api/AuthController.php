<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiLoginRequest;
use App\Http\Requests\Api\ApiRegisterRequest;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponses;

    /**
     * Register a new user.
     */
    public function register(ApiRegisterRequest $request): JsonResponse
    {
        // Authorization handled in ApiRegisterRequest
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $tokenData = $this->createToken($user);

        return $this->created('Registration successful', $tokenData);
    }

    /**
     * Login an existing user.
     */
    public function login(ApiLoginRequest $request): JsonResponse
    {
        // Authorization handled in ApiLoginRequest
        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->unauthorized('Invalid credentials.');
        }

        $user = Auth::user();

        // Revoke all previous tokens
        $user->tokens()->delete();

        $tokenData = $this->createToken($user);

        return $this->ok('User logged in successfully', $tokenData);
    }

    /**
     * Logout current user.
     */
    public function logout(): JsonResponse
    {
        $user = Auth::user();

        // Revoke all tokens
        $user->tokens()->delete();

        return $this->ok('Logged out successfully');
    }

    /**
     * Create token data for response.
     */
    private function createToken(User $user): array
    {
        $token = $user->createToken("API Token for {$user->email}");

        return [
            'user'       => $user->only('id', 'name', 'email'),
            'token'      => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => null, // If you add expiration later
        ];
    }
}
