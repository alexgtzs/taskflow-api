<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Requests\Api\V1\Auth\LoginRequest;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'Usuario registrado exitosamente',
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token']
            ]
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return response()->json([
            'message' => 'Usuario logueado exitosamente',
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token']
            ]
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => 'Successfully logged out.'
        ], 200);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles');

        return response()->json([
            'data' => new UserResource($user)
        ], 200);
    }
}
