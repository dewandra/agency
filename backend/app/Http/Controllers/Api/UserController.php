<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Get all users with pagination
     * GET /api/users
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'role' => $request->query('role'),
                'is_active' => $request->query('is_active'),
                'search' => $request->query('search'),
                'sort_by' => $request->query('sort_by', 'created_at'),
                'sort_order' => $request->query('sort_order', 'desc'),
                'per_page' => $request->query('per_page', 10),
            ];

            $users = $this->userService->getAllUsers($filters);

            return ResponseHelper::paginated(
                $users->items(),
                [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ],
                'Users retrieved successfully'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to retrieve users', 500, null, $e->getMessage());
        }
    }

    /**
     * Get user by ID
     * GET /api/users/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->userService->getUserById($id);

            if (!$user) {
                return ResponseHelper::notFound('User');
            }

            return ResponseHelper::success($user, 'User retrieved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to retrieve user', 500, null, $e->getMessage());
        }
    }

    /**
     * Create new user
     * POST /api/users
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser($request->validated());
            return ResponseHelper::created($user, 'User created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to create user', 500, null, $e->getMessage());
        }
    }

    /**
     * Update user
     * PUT /api/users/{id}
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        try {
            $user = $this->userService->updateUser($id, $request->validated());
            return ResponseHelper::success($user, 'User updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseHelper::notFound('User');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to update user', 500, null, $e->getMessage());
        }
    }

    /**
     * Delete user (soft delete)
     * DELETE /api/users/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->userService->deleteUser($id);
            return ResponseHelper::deleted('User deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseHelper::notFound('User');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to delete user', 500, null, $e->getMessage());
        }
    }

    /**
     * Restore soft deleted user
     * POST /api/users/{id}/restore
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $user = $this->userService->restoreUser($id);
            return ResponseHelper::success($user, 'User restored successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseHelper::notFound('User');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to restore user', 500, null, $e->getMessage());
        }
    }

    /**
     * Permanently delete user
     * DELETE /api/users/{id}/force
     */
    public function forceDestroy(int $id): JsonResponse
    {
        try {
            $this->userService->forceDeleteUser($id);
            return ResponseHelper::deleted('User permanently deleted');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseHelper::notFound('User');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to delete user', 500, null, $e->getMessage());
        }
    }

    /**
     * Toggle user active status
     * POST /api/users/{id}/toggle-status
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $user = $this->userService->toggleUserStatus($id);
            return ResponseHelper::success($user, 'User status updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseHelper::notFound('User');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to toggle status', 500, null, $e->getMessage());
        }
    }

    /**
     * Change user role
     * PUT /api/users/{id}/role
     */
    public function changeRole(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|in:ADMIN,EDITOR'
        ], [
            'role.required' => 'Role field is required.',
            'role.in' => 'The selected role is invalid. Allowed values: ADMIN, EDITOR.',
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
            $user = $this->userService->changeUserRole($id, $request->role);
            return ResponseHelper::success($user, 'User role changed successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseHelper::notFound('User');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to change role', 500, null, $e->getMessage());
        }
    }

    /**
     * Get user statistics
     * GET /api/users/statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->userService->getUserStatistics();
            return ResponseHelper::success($stats, 'Statistics retrieved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to retrieve statistics', 500, null, $e->getMessage());
        }
    }

    /**
     * Bulk delete users
     * POST /api/users/bulk-delete
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|integer|exists:users,id'
        ], [
            'user_ids.required' => 'User IDs are required.',
            'user_ids.array' => 'User IDs must be an array.',
            'user_ids.min' => 'At least one user ID is required.',
            'user_ids.*.integer' => 'Each user ID must be an integer.',
            'user_ids.*.exists' => 'One or more user IDs do not exist.',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::validationError($validator->errors());
        }

        try {
            $deletedCount = $this->userService->bulkDeleteUsers($request->user_ids);
            return ResponseHelper::success(
                ['deleted_count' => $deletedCount],
                "{$deletedCount} user(s) deleted successfully"
            );
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to delete users', 500, null, $e->getMessage());
        }
    }

    /**
     * Bulk update user status
     * POST /api/users/bulk-update-status
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|integer|exists:users,id',
            'is_active' => 'required|boolean'
        ], [
            'user_ids.required' => 'User IDs are required.',
            'user_ids.array' => 'User IDs must be an array.',
            'is_active.required' => 'Active status is required.',
            'is_active.boolean' => 'Active status must be true or false.',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::validationError($validator->errors());
        }

        try {
            $updatedCount = $this->userService->bulkUpdateStatus(
                $request->user_ids,
                $request->is_active
            );
            return ResponseHelper::success(
                ['updated_count' => $updatedCount],
                "{$updatedCount} user(s) updated successfully"
            );
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to update users', 500, null, $e->getMessage());
        }
    }
}