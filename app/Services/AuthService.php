<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
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
            // Generate OTP
            $otp = $this->generateOTP();

            // Store user data with OTP in Redis
            $userData = $data;
            $userData['otp'] = $otp;
            $userData['otp_expires_at'] = now()->addMinutes(10)->timestamp; // OTP expires in 10 minutes

            // Use Redis to store registration data with 10-minute expiration
            $key = 'pending_registration:' . $data['email'];
            Redis::setex($key, 600, json_encode($userData)); // 600 seconds = 10 minutes

            // Send OTP via email
            $this->sendOTPEmail($data['email'], $otp);

            return [
                'success' => true,
                'message' => 'OTP sent to your email. Please verify to complete registration.',
                'email' => $data['email']
            ];
        } catch (Exception $e) {
            Log::error('Registration failed: ' . $e->getMessage());
            throw new LogicException('Registration failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify OTP and complete registration
     */
    public function verifyOTP(string $email, string $otp)
    {
        try {
            // Get pending registration data from Redis
            $key = 'pending_registration:' . $email;
            $userDataJson = Redis::get($key);

            if (!$userDataJson) {
                throw new LogicException('Invalid or expired registration attempt');
            }

            $userData = json_decode($userDataJson, true);

            // Check if registration exists and is valid
            if (!$userData || $userData['email'] !== $email) {
                throw new LogicException('Invalid registration data');
            }

            // Check if OTP is expired
            if (now()->timestamp > $userData['otp_expires_at']) {
                throw new LogicException('OTP has expired. Please request a new one');
            }

            // Verify OTP
            if ($userData['otp'] !== $otp) {
                throw new LogicException('Invalid OTP');
            }

            // Remove OTP data to create user
            unset($userData['otp']);
            unset($userData['otp_expires_at']);

            // Create user
            $user = $this->userRepository->create($userData);

            // Clean up Redis data
            Redis::del($key);

            return [
                'success' => true,
                'user' => $user,
                'token' => JWTAuth::fromUser($user)
            ];
        } catch (Exception $e) {
            Log::error('OTP verification failed: ' . $e->getMessage());
            throw new LogicException('Verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate a random 6-digit OTP
     */
    private function generateOTP()
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Send OTP to user's email
     */
    private function sendOTPEmail(string $email, string $otp)
    {
        \Mail::to($email)->send(new \App\Mail\OtpVerification($otp));
    }

    /**
     * Resend OTP if expired or not received
     */
    public function resendOTP(string $email)
    {
        try {
            // Get pending registration data from Redis
            $key = 'pending_registration:' . $email;
            $userDataJson = Redis::get($key);

            if (!$userDataJson) {
                throw new LogicException('No pending registration found for this email');
            }

            $userData = json_decode($userDataJson, true);

            if (!$userData || $userData['email'] !== $email) {
                throw new LogicException('No pending registration found for this email');
            }

            // Generate new OTP
            $otp = $this->generateOTP();
            $userData['otp'] = $otp;
            $userData['otp_expires_at'] = now()->addMinutes(10)->timestamp;

            // Update Redis
            Redis::setex($key, 600, json_encode($userData)); // 600 seconds = 10 minutes

            // Send new OTP
            $this->sendOTPEmail($email, $otp);

            return [
                'success' => true,
                'message' => 'OTP resent to your email',
                'email' => $email
            ];
        } catch (Exception $e) {
            throw new LogicException('Failed to resend OTP: ' . $e->getMessage());
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
        try {
            $token = JWTAuth::getToken();
            if ($token) {
                JWTAuth::invalidate($token);
            }
            auth()->logout();
            return true;
        } catch (Exception $e) {
            throw new LogicException('Logout failed');
        }
    }

    public function getAuthenticatedUser()
    {
        return auth()->user();
    }
}
