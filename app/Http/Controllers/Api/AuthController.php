<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {
    }

    public function register(RegisterRequest $request)
    {
        $result = $this->authService->register($request->validated());

        return ApiResponse::success('Account created successfully.', [
            'token' => $result['token'],
            'user' => UserResource::make($result['user'])->resolve(),
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated());

        return ApiResponse::success('Login successful.', [
            'token' => $result['token'],
            'user' => UserResource::make($result['user'])->resolve(),
        ]);
    }

    public function me(Request $request)
    {
        return ApiResponse::success('Authenticated user fetched successfully.', [
            'user' => UserResource::make($request->user())->resolve(),
        ]);
    }

    public function logout(Request $request)
    {
        $this->authService->logout(
            $request->user(),
            $request->user()->currentAccessToken()?->id
        );

        return ApiResponse::success('Logged out successfully.');
    }
}
