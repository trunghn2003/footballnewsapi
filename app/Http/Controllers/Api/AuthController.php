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
use App\Http\Requests\Auth\ProfileRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    protected $authService;
    use ApiResponseTrait;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        try {
            $result = $this->authService->register($request->validated());
            return $this->successResponse($result, 'User registered successfully', 201);
        } catch (LogicException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $fcm_token = $request->fcm_token ?? null;
            $token = $this->authService->login($request->validated(), $fcm_token);
            return $this->successResponse($token, 'Login successful');
        } catch (LogicException $e) {
            Log::error('Login error: ' . $e->getMessage(), [
            ]);
            return $this->errorResponse($e->getMessage(), 401);
        }
    }

    public function logout()
    {
        try {
            $this->authService->logout();
            return $this->successResponse(null, 'Successfully logged out');
        } catch (LogicException $e) {
            return $this->errorResponse($e->getMessage(), 401);
        }
    }

    public function me()
    {
        $user = $this->authService->getAuthenticatedUser();
        if ($user->avatar) {
            $user->avatar = config('app.url') . '/storage/' . $user->avatar;
        }
        return $this->successResponse($user);
    }

    public function updateProfile(ProfileRequest $request)
    {
        try {
            $user = auth()->user();
            $data = $request->validated();

            if ($request->hasFile('avatar')) {
                if ($user->avatar) {
                    Storage::delete($user->avatar);
                }
                $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
            }

            $user->update($data);

            // Add full URL to avatar
            if ($user->avatar) {
                $user->avatar = config('app.url') . '/storage/' . $user->avatar;
            }

            return $this->successResponse($user, 'Profile updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function deleteAvatar()
    {
        try {
            $user = auth()->user();
            if ($user->avatar) {
                Storage::delete($user->avatar);
                $user->update(['avatar' => null]);
            }
            return $this->successResponse(null, 'Avatar deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
    /**
     * verify OTP for registration
     */
    public function verifyOTP(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'otp' => 'required|string|size:6'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first(), 422);
            }

            $result = $this->authService->verifyOTP($request->email, $request->otp);
            return $this->successResponse($result, 'OTP verified successfully', 200);
        } catch (LogicException $e) {
            Log::error('OTP verification error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Resend OTP for registration
     */
    public function resendOTP(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'email' => 'required|email'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first(), 422);
            }

            $result = $this->authService->resendOTP($request->email);
            return $this->successResponse($result, 'OTP resent successfully', 200);
        } catch (LogicException $e) {
            Log::error('Resend OTP error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Request password reset OTP
     */
    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first(), 422);
            }

            $result = $this->authService->forgotPassword($request->email);
            return $this->successResponse($result, 'Password reset OTP sent successfully', 200);
        } catch (LogicException $e) {
            Log::error('Forgot password error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Verify password reset OTP
     */
    public function verifyResetPasswordOTP(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'otp' => 'required|string|size:6'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first(), 422);
            }

            $result = $this->authService->verifyResetPasswordOTP($request->email, $request->otp);
            return $this->successResponse($result, 'Password reset OTP verified successfully', 200);
        } catch (LogicException $e) {
            Log::error('OTP verification error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Reset password after OTP verification
     */
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first(), 422);
            }

            $result = $this->authService->resetPassword($request->email, $request->password);
            return $this->successResponse($result, 'Password reset successfully', 200);
        } catch (LogicException $e) {
            Log::error('Reset password error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

     /**
     * Change password for authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first(), 422);
            }

            $result = $this->authService->changePassword(
                $request->current_password,
                $request->password
            );

            return $this->successResponse($result, 'Mật khẩu đã được thay đổi thành công', 200);
        } catch (LogicException $e) {
            Log::error('Change password error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
