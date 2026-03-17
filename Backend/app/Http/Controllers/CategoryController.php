<?php

namespace App\Http\Controllers;

use App\Actions\Categories\CreateCategoryAction;
use App\Actions\Categories\DeleteCategoryAction;
use App\Actions\Categories\UpdateCategoryAction;
use App\Http\Requests\DeleteCategoryRequest;
use App\Http\Requests\GetNextCategoryItemRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CategoryWithItemsResource;
use App\Http\Resources\ItemResource;
use App\Models\Category;
use App\Queries\Categories\GetCategoryRankingQuery;
use App\Queries\Categories\GetCategoryWithItemsQuery;
use App\Queries\Categories\ListCategoriesQuery;
use App\Queries\Items\GetNextCategoryItemQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function __construct(
        protected ListCategoriesQuery $listCategoriesQuery,
        protected GetCategoryWithItemsQuery $getCategoryWithItemsQuery,
        protected GetCategoryRankingQuery $getCategoryRankingQuery,
        protected GetNextCategoryItemQuery $getNextCategoryItemQuery,
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

        return $this->respondData(CategoryResource::collection($categories));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->createCategoryAction->execute($request->validated());

        return $this->respondMutation('Categoría creada con éxito.', new CategoryResource($category), status: 201);
    }

    /**
     * Display the specified category with its items (no sorting).
     */
    public function show(Category $category): JsonResponse
    {
        $categoryWithItems = $this->getCategoryWithItemsQuery->execute($category);

        return $this->respondData(new CategoryWithItemsResource($categoryWithItems));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $updatedCategory = $this->updateCategoryAction->execute($category, $request->validated());

        return $this->respondMutation('Categoría actualizada correctamente.', new CategoryResource($updatedCategory));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeleteCategoryRequest $request, Category $category): JsonResponse
    {

        $categoryName = $category->name;
        $this->deleteCategoryAction->execute($category);

        return $this->respondMutation("Categoría '{$categoryName}' eliminada correctamente.");
    }

    /**
     * Display category ranking
     */
    public function ranking(Category $category): JsonResponse
    {
        $items = $this->getCategoryRankingQuery->execute($category);

        return $this->respondData([
            'category' => new CategoryResource($category),
            'ranking' => ItemResource::collection($items),
        ]);
    }

    public function nextItem(GetNextCategoryItemRequest $request, Category $category): JsonResponse|Response
    {
        $user = $request->user();
        $result = $this->getNextCategoryItemQuery->execute($user, $category);

        if ($result === null) {
            return response()->noContent();
        }

        return $this->respondData(
            data: new ItemResource($result['item']),
            meta: [
                'remaining' => $result['remaining'],
            ],
        );
    }
}
