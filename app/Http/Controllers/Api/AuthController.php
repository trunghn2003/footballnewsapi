<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use LogicException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;


class AuthController extends Controller {
    protected $authService;
    use ApiResponseTrait;

    public function __construct(AuthService $authService) {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request) {
        try {
            $result = $this->authService->register($request->validated());
            return $this->successResponse($result, 'User registered successfully', 201);
        } catch (LogicException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function login(LoginRequest $request) {
        try {
            $token = $this->authService->login($request->validated());
            return $this->successResponse($token, 'Login successful');
        } catch (LogicException $e) {
            return $this->errorResponse($e->getMessage(), 401);
        }
    }

    public function logout() {
        $this->authService->logout();
        return $this->successResponse(null, 'Successfully logged out');
    }

    public function me() {
        $user = $this->authService->getAuthenticatedUser();
        return $this->successResponse($user);
    }
}