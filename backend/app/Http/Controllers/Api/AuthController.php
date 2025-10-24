<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Login user
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ], [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $messages) {
                $errors[$field] = $messages[0];
            }
            return response()->json([
                'status' => 422,
                'error' => 'Validation Error',
                'details' => $errors,
            ], 422);
        }

        try {
            $deviceInfo = [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            $result = $this->authService->login(
                $request->email,
                $request->password,
                $deviceInfo
            );

            return ResponseHelper::success($result, 'Login successful');
        } catch (\Exception $e) {
            return ResponseHelper::unauthorized($e->getMessage());
        }
    }

    /**
     * Refresh access token
     * POST /api/auth/refresh
     */
    public function refresh(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ], [
            'refresh_token.required' => 'Refresh token is required.',
        ]);

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $messages) {
                $errors[$field] = $messages[0];
            }
            return response()->json([
                'status' => 422,
                'error' => 'Validation Error',
                'details' => $errors,
            ], 422);
        }

        try {
            $result = $this->authService->refresh($request->refresh_token);
            return ResponseHelper::success($result, 'Token refreshed successfully');
        } catch (\Exception $e) {
            return ResponseHelper::unauthorized($e->getMessage());
        }
    }

    /**
     * Logout current session
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout(
                auth()->user(),
                $request->input('refresh_token')
            );

            return ResponseHelper::success(null, 'Logged out successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Logout failed', 500, null, $e->getMessage());
        }
    }

    /**
     * Logout all sessions
     * POST /api/auth/logout-all
     */
    public function logoutAll(Request $request): JsonResponse
    {
        try {
            $this->authService->logoutAll(auth()->user());
            return ResponseHelper::success(null, 'Logged out from all devices successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Logout failed', 500, null, $e->getMessage());
        }
    }

    /**
     * Get authenticated user profile
     * GET /api/auth/profile
     */
    public function profile(): JsonResponse
    {
        try {
            $user = $this->authService->getProfile(auth()->user());
            return ResponseHelper::success($user, 'Profile retrieved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to get profile', 500, null, $e->getMessage());
        }
    }

    /**
     * Update user profile
     * PUT /api/auth/profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . auth()->id(),
            'password' => ['sometimes', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ], [
            'name.string' => 'Name must be a valid string.',
            'name.max' => 'Name cannot exceed 255 characters.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already in use.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::validationError($validator->errors());
        }

        try {
            $user = $this->authService->updateProfile(
                auth()->user(),
                $validator->validated()
            );

            return ResponseHelper::success($user, 'Profile updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to update profile', 500, null, $e->getMessage());
        }
    }
}