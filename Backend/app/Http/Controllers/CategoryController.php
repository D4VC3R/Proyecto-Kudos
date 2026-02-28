<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CategoryWithItemsResource;
use App\Http\Resources\ItemResource;
use App\Models\Category;
use App\Services\CategoryService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
	protected CategoryService $categoryService;

	public function __construct(CategoryService $categoryService)
	{
		$this->categoryService = $categoryService;
	}

	/**
	 * Display a listing of all categories.
	 */
	public function index(): JsonResponse
	{
		$categories = $this->categoryService->getAllCategories();
		return response()->json([
			'data' => CategoryResource::collection($categories),
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(StoreCategoryRequest $request)
	{
		try {
			$category = $this->categoryService->createCategory($request->validated());

			return response()->json([
				'message' => 'Categoría creada con éxito.',
				'data' => new CategoryResource($category),
			], 201);

		} catch (Exception $e) {
			return response()->json([
				'message' => 'Error al crear la categoría.',
				'error' => $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Display the specified category with its items (no sorting).
	 */
	public function show(Category $category): JsonResponse
	{
		$categoryWithItems = $this->categoryService->getCategoryWithItems($category);

		return response()->json([
			'data' => new CategoryWithItemsResource($categoryWithItems),
		]);
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
	{
		try {
			$updatedCategory = $this->categoryService->updateCategory(
				$category,
				$request->validated()
			);

			return response()->json([
				'message' => 'Categoría actualizada correctamente.',
				'data' => new CategoryResource($updatedCategory),
			]);

		} catch (Exception $e) {
			return response()->json([
				'message' => 'No se ha podido actualizar la categoría.',
				'error' => $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(Category $category)
	{
		try {
			$categoryName = $category->name;
			$this->categoryService->deleteCategory($category);

			return response()->json([
				'message' => "Categoría '{$categoryName}' eliminada correctamente.",
			]);

		} catch (Exception $e) {
			return response()->json([
				'message' => $e->getMessage(),
			], $e->getCode());
		}
	}

	/**
	 * Display category ranking
	 */
	public function ranking(Category $category): JsonResponse
	{
		$items = $this->categoryService->getCategoryRanking($category);

		return response()->json([
			'category' => new CategoryResource($category),
			'ranking' => ItemResource::collection($items),
		]);
	}
}