<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class CategoryService
{
    /**
     * Get all categories with filters
     */
    public function getAllCategories(array $filters = []): LengthAwarePaginator
    {
        $query = Category::query();

        // Filter by type
        if (isset($filters['type']) && in_array($filters['type'], ['article', 'video'])) {
            $query->byType($filters['type']);
        }

        // Filter by active status
        if (isset($filters['is_active'])) {
            $isActive = filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        // Search by name
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'order';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        
        if (in_array($sortBy, ['name', 'type', 'order', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->ordered();
        }

        // Pagination
        $perPage = $filters['per_page'] ?? 10;
        $perPage = min(max($perPage, 1), 100);

        return $query->paginate($perPage);
    }

    /**
     * Get category by ID
     */
    public function getCategoryById(int $id): ?Category
    {
        return Category::find($id);
    }

    /**
     * Get category by slug
     */
    public function getCategoryBySlug(string $slug): ?Category
    {
        return Category::where('slug', $slug)->first();
    }

    /**
     * Create new category
     */
    public function createCategory(array $data): Category
    {
        // Auto-generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Ensure slug is unique
        $data['slug'] = $this->ensureUniqueSlug($data['slug']);

        return Category::create($data);
    }

    /**
     * Update category
     */
    public function updateCategory(int $id, array $data): Category
    {
        $category = Category::findOrFail($id);

        // If name changed and slug not provided, regenerate slug
        if (isset($data['name']) && $data['name'] !== $category->name && empty($data['slug'])) {
            $data['slug'] = $this->ensureUniqueSlug(Str::slug($data['name']), $id);
        }

        $category->update($data);

        return $category->fresh();
    }

    /**
     * Delete category
     */
    public function deleteCategory(int $id): bool
    {
        $category = Category::findOrFail($id);

        // TODO: Check if category has articles/videos
        // Prevent deletion if there are associated content
        
        return $category->delete();
    }

    /**
     * Reorder categories
     */
    public function reorderCategories(array $categoryOrders): bool
    {
        foreach ($categoryOrders as $item) {
            if (isset($item['id']) && isset($item['order'])) {
                Category::where('id', $item['id'])->update(['order' => $item['order']]);
            }
        }

        return true;
    }

    /**
     * Toggle category status
     */
    public function toggleStatus(int $id): Category
    {
        $category = Category::findOrFail($id);
        $category->update(['is_active' => !$category->is_active]);

        return $category->fresh();
    }

    /**
     * Get categories by type (for public use)
     */
    public function getCategoriesByType(string $type, bool $activeOnly = true)
    {
        $query = Category::byType($type);

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    /**
     * Get category statistics
     */
    public function getCategoryStatistics(): array
    {
        return [
            'total' => Category::count(),
            'active' => Category::where('is_active', true)->count(),
            'inactive' => Category::where('is_active', false)->count(),
            'by_type' => [
                'article' => Category::where('type', 'article')->count(),
                'video' => Category::where('type', 'video')->count(),
            ],
        ];
    }

    /**
     * Ensure slug is unique
     */
    private function ensureUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $query = Category::where('slug', $slug);
            
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}