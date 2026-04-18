<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteCategoryRequest;
use App\Http\Requests\GetNextCategoryItemRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryRankingResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CategoryWithItemsResource;
use App\Http\Resources\ItemDetailResource;
use App\Models\Category;
use App\Services\CategoryService;
use App\Services\NextCategoryItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService,
        protected NextCategoryItemService $nextCategoryItemService,
    ) {
    }

    /**
     * Display a listing of all categories.
     */
    public function index(): JsonResponse
    {
        $categories = $this->categoryService->getAllCategories();

        return $this->respondData(CategoryResource::collection($categories));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->createCategory($request->validated());

        return $this->respondMutation('Categoría creada con éxito.', new CategoryResource($category), status: 201);
    }

    /**
     * Display the specified category with its items (no sorting).
     */
    public function show(Category $category): JsonResponse
    {
        $categoryWithItems = $this->categoryService->getCategoryWithItems($category);

        return $this->respondData(new CategoryWithItemsResource($categoryWithItems));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $updatedCategory = $this->categoryService->updateCategory($category, $request->validated());

        return $this->respondMutation('Categoría actualizada correctamente.', new CategoryResource($updatedCategory));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeleteCategoryRequest $request, Category $category): JsonResponse
    {

        $categoryName = $category->name;
        $this->categoryService->deleteCategory($category);

        return $this->respondMutation("Categoría '{$categoryName}' eliminada correctamente.");
    }

    /**
     * Display category ranking
     */
    public function ranking(Request $request, Category $category): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $items = $this->categoryService->getCategoryRanking($category, $perPage);

        return $this->respondData(new CategoryRankingResource([
            'category' => $category,
            'ranking' => $items,
        ]), meta: [
            'total' => $items->total(),
            'current_page' => $items->currentPage(),
            'last_page' => $items->lastPage(),
            'per_page' => $items->perPage(),
        ]);
    }

    public function nextItem(GetNextCategoryItemRequest $request, Category $category): JsonResponse|Response
    {
        $user = $request->user();
        $result = $this->nextCategoryItemService->getNextItem($user, $category);
        if ($result === null) {
            return response()->noContent();
        }

        return $this->respondData(
            data: new ItemDetailResource($result['item']),
            meta: [
                'remaining' => $result['remaining'],
            ],
        );
    }
}
