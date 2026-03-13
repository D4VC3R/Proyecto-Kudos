<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Services\ItemService;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ItemController extends Controller
{
	protected ItemService $itemService;

	public function __construct(ItemService $itemService)
	{
		$this->itemService = $itemService;
	}

	/**
	 * Display a listing of active items.
	 */
	public function index(Request $request): JsonResponse
	{
		$filters = [
			'category_id' => $request->query('category_id'),
			'search' => $request->query('search'),
			'tag_ids' => $request->query('tag_ids', []),
			'sort_by' => $request->query('sort_by', 'vote_avg'),
			'sort_order' => $request->query('sort_order', 'desc'),
			'exclude_voted_by' => ($request->query('sort_by') === 'random' && $request->user())
				? $request->user()->id
				: null,
		];

		$perPage = min(max((int)$request->query('per_page', 15), 1), 100);

		$items = $this->itemService->getActiveItems($filters, $perPage);

		return response()->json([
			'data' => ItemResource::collection($items),
			'meta' => [
				'current_page' => $items->currentPage(),
				'last_page' => $items->lastPage(),
				'per_page' => $items->perPage(),
				'total' => $items->total(),
			],
			'links' => [
				'first' => $items->url(1),
				'last' => $items->url($items->lastPage()),
				'prev' => $items->previousPageUrl(),
				'next' => $items->nextPageUrl(),
			],
		]);
	}

	/**
	 * Store a newly created item.
	 */
	public function store(StoreItemRequest $request): JsonResponse
	{
		Gate::authorize('create', Item::class);

		$item = $this->itemService->createItem(
			$request->validated(),
			$request->user()
		);

		return response()->json([
			'message' => 'Item creado correctamente.',
			'data' => new ItemResource($item),
		], 201);
	}

	/**
	 * Display the specified item.
	 */
	public function show(Item $item): JsonResponse
	{
		Gate::authorize('view', $item);

		$item->load(['category', 'creator', 'tags']);

		// ✅ ItemResource calcula automáticamente
		return response()->json([
			'data' => new ItemResource($item),
		]);
	}

	/**
	 * Update the specified item.
	 */
	public function update(UpdateItemRequest $request, Item $item): JsonResponse
	{
		Gate::authorize('update', $item);
		$updatedItem = $this->itemService->updateItem($item, $request->validated());

		return response()->json([
			'message' => 'Item actualizado correctamente.',
			'data' => new ItemResource($updatedItem),
		]);
	}

	/**
	 * Remove the specified item.
	 */
	public function destroy(Item $item): JsonResponse
	{
		Gate::authorize('delete', $item);

		try {
			$this->itemService->deleteItem($item);
			return response()->json([
				'message' => 'Item eliminado correctamente.',
			]);
		} catch (Exception $e) {
			return response()->json([
				'message' => 'No se ha podido eliminar el item.',
				'error' => $e->getMessage(),
			], 400);
		}
	}

	/**
	 * Get items created by the authenticated user.
	 */
	public function myItems(Request $request): JsonResponse
	{
		$items = $this->itemService->getItemsByUser($request->user());

		$this->itemService->enrichItemsWithUserContext($items, $request->user());

		return response()->json([
			'data' => ItemResource::collection($items),
			'meta' => [
				'total' => $items->count(),
				'active' => $items->where('status', Item::STATUS_ACTIVE)->count(),
				'inactive' => $items->where('status', Item::STATUS_INACTIVE)->count(),
			],
		]);
	}

	// Moderación movida a ProposalController (Paso 3):
	// - pending
	// - accept
	// - reject
	// - forceDestroy
}
