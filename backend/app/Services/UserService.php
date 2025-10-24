<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    /**
     * Get all users with pagination and filters
     */
    public function getAllUsers(array $filters = []): LengthAwarePaginator
    {
        $query = User::query();

        // Filter by role
        if (isset($filters['role']) && in_array($filters['role'], ['ADMIN', 'EDITOR', 'VIEWER'])) {
            $query->where('role', $filters['role']);
        }

        // Filter by active status
        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        // Search by name or email
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        
        if (in_array($sortBy, ['name', 'email', 'role', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $perPage = $filters['per_page'] ?? 10;
        $perPage = min(max($perPage, 1), 100); // Between 1 and 100

        return $query->paginate($perPage);
    }

    /**
     * Get user by ID
     */
    public function getUserById(int $id): ?User
    {
        return User::with('refreshTokens')->find($id);
    }

    /**
     * Create new user
     */
    public function createUser(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        
        return User::create($data);
    }

    /**
     * Update existing user
     */
    public function updateUser(int $id, array $data): User
    {
        $user = User::findOrFail($id);

        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return $user->fresh();
    }

    /**
     * Delete user (soft delete)
     */
    public function deleteUser(int $id): bool
    {
        $user = User::findOrFail($id);

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            throw new \Exception('You cannot delete your own account');
        }

        return $user->delete();
    }

    /**
     * Restore soft deleted user
     */
    public function restoreUser(int $id): User
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        return $user->fresh();
    }

    /**
     * Permanently delete user
     */
    public function forceDeleteUser(int $id): bool
    {
        $user = User::withTrashed()->findOrFail($id);

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            throw new \Exception('You cannot delete your own account');
        }

        return $user->forceDelete();
    }

    /**
     * Toggle user active status
     */
    public function toggleUserStatus(int $id): User
    {
        $user = User::findOrFail($id);

        // Prevent deactivating yourself
        if ($user->id === auth()->id()) {
            throw new \Exception('You cannot deactivate your own account');
        }

        $user->update(['is_active' => !$user->is_active]);

        return $user->fresh();
    }

    /**
     * Change user role
     */
    public function changeUserRole(int $id, string $role): User
    {
        $user = User::findOrFail($id);

        // Prevent changing your own role
        if ($user->id === auth()->id()) {
            throw new \Exception('You cannot change your own role');
        }

        $user->update(['role' => $role]);

        return $user->fresh();
    }

    /**
     * Get user statistics
     */
    public function getUserStatistics(): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
            'users_by_role' => [
                'admin' => User::where('role', 'ADMIN')->count(),
                'editor' => User::where('role', 'EDITOR')->count(),
                'viewer' => User::where('role', 'VIEWER')->count(),
            ],
            'recently_created' => User::where('created_at', '>=', now()->subDays(7))->count(),
            'soft_deleted' => User::onlyTrashed()->count(),
        ];
    }

    /**
     * Bulk delete users
     */
    public function bulkDeleteUsers(array $userIds): int
    {
        // Remove current user from the list
        $userIds = array_filter($userIds, fn($id) => $id !== auth()->id());

        return User::whereIn('id', $userIds)->delete();
    }

    /**
     * Bulk update user status
     */
    public function bulkUpdateStatus(array $userIds, bool $isActive): int
    {
        // Remove current user from the list
        $userIds = array_filter($userIds, fn($id) => $id !== auth()->id());

        return User::whereIn('id', $userIds)->update(['is_active' => $isActive]);
    }
}