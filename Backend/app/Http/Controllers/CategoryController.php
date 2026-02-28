<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CategoryWithItemsResource;
use App\Http\Resources\ItemResource;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
	/**
	 * Display a listing of all categories.
	 */
	public function index()
	{
		$categories = Category::orderBy('name')->get();
		return CategoryResource::collection($categories);
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(StoreCategoryRequest $request)
	{
		$category = Category::create([
			'id' => Str::uuid(),
			'name' => $request->name,
			'description' => $request->description,
			'slug' => $request->slug,
			'image' => $request->image,
		]);

		return response()->json([
			'message' => 'Categoría creada exitosamente.',
			'data' => new CategoryResource($category),
		], 201);
	}

	/**
	 * Display the specified category with its items (no sorting).
	 */
	public function show(Category $category)
	{
		$category->load([
			'items' => function ($query) {
				$query->where('state', Item::STATE_ACCEPTED)
				->with(['creator:id,name', 'tags:id,name'])
					->latest();
			}
		]);

		return new CategoryWithItemsResource($category);
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
	{
		$category->update($request->validated());

		return response()->json([
			'message' => 'Categoría actualizada exitosamente.',
			'data' => new CategoryResource($category),
		], 200);
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(Category $category)
	{
		if ($category->items()->exists()) {
			return response()->json([
				'message' => 'No se puede eliminar la categoría porque tiene items asociados.',
				'items_count' => $category->items()->count(),
			], 409); // 409 Conflict
		}

		$categoryName = $category->name;
		$category->delete();

		return response()->json([
			'message' => "Categoría '{$categoryName}' eliminada correctamente.",
		], 200);
	}

	/**
	 * Display category ranking
	 */
	public function ranking(Category $category): \Illuminate\Http\JsonResponse
	{

		// Cargar items aceptados ordenados por puntuación
		$items = $category->items()
			->where('state', Item::STATE_ACCEPTED)
			->with('creator:id,name')
			->orderByDesc('vote_avg')
			->orderByDesc('vote_count')
			->orderBy('name')
			->get();


		return response()->json([
			'category' => new CategoryResource($category),
			'ranking' => ItemResource::collection($items),
					]);
	}
}