<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Traits\ApiResponses;

class AuthController extends Controller
{
    use ApiResponses;

    public function login(LoginRequest $request)
    {
        return $this->ok('Hello login '. $request->get('email') .''. $request->get('password'));
    }
    public function register(LoginRequest $request)
    {
        return $this->ok('Registered!');
    }
}