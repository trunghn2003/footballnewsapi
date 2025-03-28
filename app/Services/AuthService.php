<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use LogicException;


class AuthService
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function register(array $data)
    {
        try {
            $user = $this->userRepository->create($data);
            return [
                'success' => true,
                'user' => $user,
                'token' => JWTAuth::fromUser($user)
            ];
        } catch (Exception $e) {
            throw new LogicException('Registration failed');
        }
    }

    public function login(array $credentials)
    {
        if (!$token = JWTAuth::attempt($credentials)) {
            throw new LogicException('Invalid credentials');
        }

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ];
    }

    public function logout()
    {
        auth()->logout();
    }

    public function getAuthenticatedUser()
    {
        return auth()->user();
    }
}
