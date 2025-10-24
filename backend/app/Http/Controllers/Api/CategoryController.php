<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
        protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Get all categories
     * GET /api/categories
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'type' => $request->query('type'),
                'is_active' => $request->query('is_active'),
                'search' => $request->query('search'),
                'sort_by' => $request->query('sort_by', 'order'),
                'sort_order' => $request->query('sort_order', 'asc'),
                'per_page' => $request->query('per_page', 10),
            ];

            $categories = $this->categoryService->getAllCategories($filters);

            return ResponseHelper::paginated(
                $categories->items(),
                [
                    'total' => $categories->total(),
                    'per_page' => $categories->perPage(),
                    'current_page' => $categories->currentPage(),
                    'last_page' => $categories->lastPage(),
                    'from' => $categories->firstItem(),
                    'to' => $categories->lastItem(),
                ],
                'Categories retrieved successfully'
            );
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to retrieve categories', 500, null, $e->getMessage());
        }
    }

    /**
     * Get category by ID
     * GET /api/categories/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $category = $this->categoryService->getCategoryById($id);

            if (!$category) {
                return ResponseHelper::notFound('Category');
            }

            return ResponseHelper::success($category, 'Category retrieved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to retrieve category', 500, null, $e->getMessage());
        }
    }

    /**
     * Create new category
     * POST /api/categories
     */
    public function store(CategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->categoryService->createCategory($request->validated());
            return ResponseHelper::created($category, 'Category created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to create category', 500, null, $e->getMessage());
        }
    }

    /**
     * Update category
     * PUT /api/categories/{id}
     */
    public function update(CategoryRequest $request, int $id): JsonResponse
    {
        try {
            $category = $this->categoryService->updateCategory($id, $request->validated());
            return ResponseHelper::success($category, 'Category updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseHelper::notFound('Category');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to update category', 500, null, $e->getMessage());
        }
    }

    /**
     * Delete category
     * DELETE /api/categories/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->categoryService->deleteCategory($id);
            return ResponseHelper::deleted('Category deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseHelper::notFound('Category');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to delete category', 500, null, $e->getMessage());
        }
    }

    /**
     * Reorder categories
     * PUT /api/categories/reorder
     */
    public function reorder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'categories' => 'required|array',
            'categories.*.id' => 'required|integer|exists:categories,id',
            'categories.*.order' => 'required|integer|min:0',
        ], [
            'categories.required' => 'Categories array is required.',
            'categories.*.id.required' => 'Category ID is required.',
            'categories.*.id.exists' => 'One or more category IDs do not exist.',
            'categories.*.order.required' => 'Order is required for each category.',
            'categories.*.order.integer' => 'Order must be a number.',
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
            $this->categoryService->reorderCategories($request->categories);
            return ResponseHelper::success(null, 'Categories reordered successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to reorder categories', 500, null, $e->getMessage());
        }
    }
}
