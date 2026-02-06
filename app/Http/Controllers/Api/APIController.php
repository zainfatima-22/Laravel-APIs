<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiLoginRequest;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class APIController extends Controller
{
    use ApiResponses;

    /**
     * Handle user registration and token issuance. (201 Created)
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $tokenResult = $user->createToken('API Token For ' . $user->email);

        return $this->created('Registration successful and authenticated', [
            'user' => $user->only('id', 'name', 'email'), 
            'token' => $tokenResult->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => null, 
        ]);
    }

    /**
     * Handle user login and token issuance. (200 OK or 401 Unauthorized)
     */
    public function login(ApiLoginRequest $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->unauthorized('Invalid Credentials');
        }
        
        $user = User::firstWhere('email', $request->email);

        $tokenResult = $user->createToken('API Token For ' . $user->email);

        return $this->ok('authenticated', [
            'token' => $tokenResult->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => null,
        ]);
    }

    /**
     * Handle user logout (revoking the current access token). (200 OK)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->ok('Logout Successfully');
    }
}