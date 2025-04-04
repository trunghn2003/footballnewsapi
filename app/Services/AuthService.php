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

    public function login(array $credentials, $fcm_token)
    {
        \Log::info('Login attempt with credentials: ', $credentials);
        if (!$token = JWTAuth::attempt($credentials)) {
            throw new LogicException('Invalid credentials');
        }
        $user = $this->getAuthenticatedUser();
        if ($fcm_token) {
            $user->fcm_token = $fcm_token;
        }
        $user->save();

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
