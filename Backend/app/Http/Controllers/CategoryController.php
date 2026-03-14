<?php

namespace App\Http\Controllers;

use App\Actions\Categories\CreateCategoryAction;
use App\Actions\Categories\DeleteCategoryAction;
use App\Actions\Categories\UpdateCategoryAction;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CategoryWithItemsResource;
use App\Http\Resources\ItemResource;
use App\Models\Category;
use App\Queries\Categories\GetCategoryRankingQuery;
use App\Queries\Categories\GetCategoryWithItemsQuery;
use App\Queries\Categories\ListCategoriesQuery;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ListCategoriesQuery $listCategoriesQuery,
        protected GetCategoryWithItemsQuery $getCategoryWithItemsQuery,
        protected GetCategoryRankingQuery $getCategoryRankingQuery,
        protected CreateCategoryAction $createCategoryAction,
        protected UpdateCategoryAction $updateCategoryAction,
        protected DeleteCategoryAction $deleteCategoryAction,
    ) {
    }

    /**
     * Display a listing of all categories.
     */
    public function index(): JsonResponse
    {
        $categories = $this->listCategoriesQuery->execute();

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);

        $category = $this->createCategoryAction->execute($request->validated());

        return response()->json([
            'message' => 'Categoría creada con éxito.',
            'data' => new CategoryResource($category),
        ], 201);
    }

    /**
     * Display the specified category with its items (no sorting).
     */
    public function show(Category $category): JsonResponse
    {
        $categoryWithItems = $this->getCategoryWithItemsQuery->execute($category);

        return response()->json([
            'data' => new CategoryWithItemsResource($categoryWithItems),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $this->authorize('update', $category);

        $updatedCategory = $this->updateCategoryAction->execute($category, $request->validated());

        return response()->json([
            'message' => 'Categoría actualizada correctamente.',
            'data' => new CategoryResource($updatedCategory),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('delete', $category);

        $categoryName = $category->name;
        $this->deleteCategoryAction->execute($category);

        return response()->json([
            'message' => "Categoría '{$categoryName}' eliminada correctamente.",
        ]);
    }

    /**
     * Display category ranking
     */
    public function ranking(Category $category): JsonResponse
    {
        $items = $this->getCategoryRankingQuery->execute($category);

        return response()->json([
            'data' => [
                'category' => new CategoryResource($category),
                'ranking' => ItemResource::collection($items),
            ],
        ]);
    }
}
