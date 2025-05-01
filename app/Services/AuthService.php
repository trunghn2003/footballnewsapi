<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use LogicException;
use App\Models\PasswordReset;
use App\Mail\ResetPasswordOtp;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

    /**
     * Send password reset OTP to user's email
     */
    public function forgotPassword(string $email)
    {
        try {
            // Check if user exists
            $user = $this->userRepository->findByEmail($email);
            if (!$user) {
                throw new LogicException('User with this email does not exist');
            }

            // Generate OTP
            $otp = $this->generateOTP();
            $expiry = now()->addMinutes(10); // 10 minutes expiry

            // Store in database
            PasswordReset::where('email', $email)->delete(); // Remove existing records
            PasswordReset::create([
                'email' => $email,
                'otp' => $otp,
                'expires_at' => $expiry,
                'created_at' => now(),
                'is_used' => false
            ]);

            // Send email with OTP
            Mail::to($email)->send(new ResetPasswordOtp($otp));

            return [
                'success' => true,
                'message' => 'Password reset OTP has been sent to your email',
                'email' => $email
            ];
        } catch (Exception $e) {
            Log::error('Forgot password failed: ' . $e->getMessage());
            throw new LogicException('Forgot password failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify OTP for password reset and enable password change
     */
    public function verifyResetPasswordOTP(string $email, string $otp)
    {
        try {
            $resetData = PasswordReset::where('email', $email)
                ->where('otp', $otp)
                ->where('is_used', false)
                ->first();

            if (!$resetData) {
                throw new LogicException('Invalid OTP');
            }

            if (now()->gt($resetData->expires_at)) {
                throw new LogicException('OTP has expired. Please request a new one');
            }

            // Mark as verified but not used until password is reset
            $resetData->update(['is_used' => true]);

            return [
                'success' => true,
                'message' => 'OTP verified successfully. You can now reset your password',
                'email' => $email
            ];
        } catch (Exception $e) {
            Log::error('Reset password OTP verification failed: ' . $e->getMessage());
            throw new LogicException('Verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Reset password after OTP verification
     */
    public function resetPassword(string $email, string $password)
    {
        try {
            // Check if user has verified OTP
            $resetData = PasswordReset::where('email', $email)
                ->where('is_used', true)
                ->first();

            if (!$resetData) {
                throw new LogicException('Please verify your OTP first');
            }

            // Update password
            $user = $this->userRepository->findByEmail($email);
            if (!$user) {
                throw new LogicException('User not found');
            }

            $user->password = Hash::make($password);
            $user->save();

            // Delete reset record
            $resetData->delete();

            return [
                'success' => true,
                'message' => 'Password has been reset successfully',
            ];
        } catch (Exception $e) {
            Log::error('Reset password failed: ' . $e->getMessage());
            throw new LogicException('Reset password failed: ' . $e->getMessage());
        }
    }

    /**
     * Change password for authenticated user
     *
     * @param string $currentPassword Current password for verification
     * @param string $newPassword New password to set
     * @return array Success status and message
     */
    public function changePassword(string $currentPassword, string $newPassword)
    {
        try {
            // Get authenticated user
            $user = $this->getAuthenticatedUser();

            if (!$user) {
                throw new LogicException('User not authenticated');
            }

            // Verify current password
            if (!Hash::check($currentPassword, $user->password)) {
                throw new LogicException('Current password is incorrect');
            }

            // Update password
            $user->password = Hash::make($newPassword);
            $user->save();

            return [
                'success' => true,
                'message' => 'Mật khẩu đã được thay đổi thành công'
            ];
        } catch (Exception $e) {
            Log::error('Change password failed: ' . $e->getMessage());
            throw new LogicException('Không thể thay đổi mật khẩu: ' . $e->getMessage());
        }
    }

   
}
