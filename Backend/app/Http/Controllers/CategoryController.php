<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CategoryWithItemsResource;
use App\Http\Resources\ItemResource;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
	/**
	 * Display a listing of all categories.
	 */
	public function index()
	{
		$categories = Category::withItemCount()
			->orderBy('name')
			->get();

		return CategoryResource::collection($categories);
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(StoreCategoryRequest $request)
	{
		//
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
	public function update(UpdateCategoryRequest $request, Category $category)
	{
		//
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(Category $category)
	{
		//
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
			'ranking' =>
				ItemResource::collection($items),
					], 200);
	}
}