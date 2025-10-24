<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class TagService
{
    /**
     * Get all tags with filters
     */
    public function getAllTags(array $filters = [])
    {
        $query = Tag::query();

        // Search by name
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        
        if (in_array($sortBy, ['name', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Check if pagination is requested
        if (isset($filters['paginate']) && $filters['paginate'] === false) {
            return $query->get();
        }

        // Pagination
        $perPage = $filters['per_page'] ?? 10;
        $perPage = min(max($perPage, 1), 100);

        return $query->paginate($perPage);
    }

    /**
     * Get tag by ID
     */
    public function getTagById(int $id): ?Tag
    {
        return Tag::find($id);
    }

    /**
     * Get tag by slug
     */
    public function getTagBySlug(string $slug): ?Tag
    {
        return Tag::where('slug', $slug)->first();
    }

    /**
     * Create new tag
     */
    public function createTag(array $data): Tag
    {
        // Auto-generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Ensure slug is unique
        $data['slug'] = $this->ensureUniqueSlug($data['slug']);

        // Set default color if not provided
        if (empty($data['color'])) {
            $data['color'] = '#3B82F6';
        }

        return Tag::create($data);
    }

    /**
     * Update tag
     */
    public function updateTag(int $id, array $data): Tag
    {
        $tag = Tag::findOrFail($id);

        // If name changed and slug not provided, regenerate slug
        if (isset($data['name']) && $data['name'] !== $tag->name && empty($data['slug'])) {
            $data['slug'] = $this->ensureUniqueSlug(Str::slug($data['name']), $id);
        }

        $tag->update($data);

        return $tag->fresh();
    }

    /**
     * Delete tag
     */
    public function deleteTag(int $id): bool
    {
        $tag = Tag::findOrFail($id);

        // TODO: Detach from all articles and videos before deleting
        // $tag->articles()->detach();
        // $tag->videos()->detach();
        
        return $tag->delete();
    }

    /**
     * Bulk delete tags
     */
    public function bulkDeleteTags(array $tagIds): int
    {
        return Tag::whereIn('id', $tagIds)->delete();
    }

    /**
     * Get or create tags by names
     */
    public function getOrCreateTags(array $tagNames): array
    {
        $tags = [];

        foreach ($tagNames as $name) {
            $name = trim($name);
            if (empty($name)) {
                continue;
            }

            $slug = Str::slug($name);
            
            $tag = Tag::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'color' => $this->getRandomColor(),
                ]
            );

            $tags[] = $tag->id;
        }

        return $tags;
    }

    /**
     * Get tag statistics
     */
    public function getTagStatistics(): array
    {
        return [
            'total' => Tag::count(),
            'recently_created' => Tag::where('created_at', '>=', now()->subDays(7))->count(),
            // Usage stats will be implemented after Articles/Videos are created
            'most_used' => [],
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
            $query = Tag::where('slug', $slug);
            
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

    /**
     * Generate random color for new tags
     */
    private function getRandomColor(): string
    {
        $colors = [
            '#3B82F6', // Blue
            '#10B981', // Green
            '#F59E0B', // Yellow
            '#EF4444', // Red
            '#8B5CF6', // Purple
            '#EC4899', // Pink
            '#06B6D4', // Cyan
            '#F97316', // Orange
        ];

        return $colors[array_rand($colors)];
    }
}