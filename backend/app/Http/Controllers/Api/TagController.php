<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\TagRequest;
use App\Services\TagService;
use App\Models\Tag; // Diperlukan untuk findOrCreate
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

class TagController extends Controller
{
    protected $tagService;

    public function __construct(TagService $tagService)
    {
        $this->tagService = $tagService;
    }

    /**
     * Get all tags
     * GET /api/tags
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->query('search'),
                'sort_by' => $request->query('sort_by', 'name'),
                'sort_order' => $request->query('sort_order', 'asc'),
                'per_page' => $request->query('per_page', 10),
                // Service Anda mendukung pengambilan non-paginasi
                'paginate' => $request->query('paginate', 'true') !== 'false',
            ];

            $tags = $this->tagService->getAllTags($filters);

            // Handle respons paginasi dan non-paginasi
            if ($tags instanceof LengthAwarePaginator) {
                return ResponseHelper::paginated(
                    $tags->items(),
                    [
                        'total' => $tags->total(),
                        'per_page' => $tags->perPage(),
                        'current_page' => $tags->currentPage(),
                        'last_page' => $tags->lastPage(),
                        'from' => $tags->firstItem(),
                        'to' => $tags->lastItem(),
                    ],
                    'Tags retrieved successfully'
                );
            }

            // Jika bukan paginasi (collection biasa)
            return ResponseHelper::success($tags, 'Tags retrieved successfully');

        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to retrieve tags', 500, null, $e->getMessage());
        }
    }

    /**
     * Get tag by ID
     * GET /api/tags/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $tag = $this->tagService->getTagById($id);

            if (!$tag) {
                return ResponseHelper::notFound('Tag');
            }

            return ResponseHelper::success($tag, 'Tag retrieved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to retrieve tag', 500, null, $e->getMessage());
        }
    }

    /**
     * Create new tag
     * POST /api/tags
     */
    public function store(TagRequest $request): JsonResponse
    {
        try {
            $tag = $this->tagService->createTag($request->validated());
            return ResponseHelper::created($tag, 'Tag created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to create tag', 500, null, $e->getMessage());
        }
    }

    /**
     * Update tag
     * PUT /api/tags/{id}
     */
    public function update(TagRequest $request, int $id): JsonResponse
    {
        try {
            $tag = $this->tagService->updateTag($id, $request->validated());
            return ResponseHelper::success($tag, 'Tag updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseHelper::notFound('Tag');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to update tag', 500, null, $e->getMessage());
        }
    }

    /**
     * Delete tag
     * DELETE /api/tags/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->tagService->deleteTag($id);
            return ResponseHelper::deleted('Tag deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseHelper::notFound('Tag');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to delete tag', 500, null, $e->getMessage());
        }
    }
    
    /**
     * Get tag statistics
     * GET /api/tags/statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->tagService->getTagStatistics();
            return ResponseHelper::success($stats, 'Tag statistics retrieved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to retrieve statistics', 500, null, $e->getMessage());
        }
    }

    /**
     * Bulk delete tags
     * POST /api/tags/bulk-delete
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:tags,id',
        ], [
            'ids.required' => 'An array of tag IDs is required.',
            'ids.*.integer' => 'All items in the IDs array must be integers.',
            'ids.*.exists' => 'One or more tag IDs do not exist.',
        ]);

        if ($validator->fails()) {
            // Asumsi ResponseHelper punya method validationError
            return ResponseHelper::error('Validation Error', 422, $validator->errors()->toArray());
        }

        try {
            $count = $this->tagService->bulkDeleteTags($request->ids);
            return ResponseHelper::success(['deleted_count' => $count], $count . ' tags deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to bulk delete tags', 500, null, $e->getMessage());
        }
    }

    /**
     * Find or create tags by name
     * POST /api/tags/find-or-create
     */
    public function findOrCreate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tags' => 'required|array',
            'tags.*' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            // Asumsi ResponseHelper punya method validationError
            return ResponseHelper::error('Validation Error', 422, $validator->errors()->toArray());
        }
        
        try {
            $tagIds = $this->tagService->getOrCreateTags($request->tags);
            // Mengembalikan objek Tag lengkap agar lebih berguna bagi frontend
            $tags = Tag::whereIn('id', $tagIds)->get();
            return ResponseHelper::success($tags, 'Tags found or created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to find or create tags', 500, null, $e->getMessage());
        }
    }
}