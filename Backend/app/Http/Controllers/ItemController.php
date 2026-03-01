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
	 * Display a listing of accepted items.
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

		$perPage = min(max((int) $request->query('per_page', 15), 1), 100);

		$items = $this->itemService->getAcceptedItems($filters, $perPage);

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
	 * Store a newly created item proposal.
	 */
	public function store(StoreItemRequest $request): JsonResponse
	{
		Gate::authorize('create', Item::class);

			$item = $this->itemService->createItem(
				$request->validated(),
				$request->user()
			);

			return response()->json([
				'message' => 'Item propuesto correctamente. Será revisado por un administrador.',
				'data' => new ItemResource($item),
			], 201);

	}

	/**
	 * Display the specified item.
	 */
	public function show(Request $request, Item $item): JsonResponse
	{

		// Autorizar visualización
		Gate::authorize('view', $item);
		$item->load(['category', 'creator', 'tags']);

		// Si hay usuario autenticado, cargar su voto
		if ($user = $request->user()) {
			$item->load(['votes' => function ($query) use ($user) {
				$query->where('user_id', $user->id);
			}]);
		}
		return response()->json([
			'data' => new ItemResource($item),
		]);
	}

	/**
	 * Update the specified item (only if pending).
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
	 * Remove the specified item (only if pending).
	 */
	public function destroy(Item $item): JsonResponse
	{
		Gate::authorize('delete', $item);

			$this->itemService->deleteItem($item);

			return response()->json([
				'message' => 'Item eliminado correctamente.',
			]);

	}

	/**
	 * Get items created by the authenticated user.
	 */
	public function myItems(Request $request): JsonResponse
	{
		$items = $this->itemService->getItemsByUser($request->user());

		return response()->json([
			'data' => ItemResource::collection($items),
			'meta' => [
				'total' => $items->count(),
				'pending' => $items->where('state', Item::STATE_PENDING)->count(),
				'accepted' => $items->where('state', Item::STATE_ACCEPTED)->count(),
				'rejected' => $items->where('state', Item::STATE_REJECTED)->count(),
			],
		]);
	}

	/**
	 * Get pending items for admin review.
	 */
	public function pending(Request $request): JsonResponse
	{

		Gate::authorize('moderate', Item::class);

		$perPage = min(max((int) $request->query('per_page', 15), 1), 100);
		$items = $this->itemService->getPendingItems($perPage);

		return response()->json([
			'data' => ItemResource::collection($items),
			'meta' => [
				'current_page' => $items->currentPage(),
				'last_page' => $items->lastPage(),
				'per_page' => $items->perPage(),
				'total' => $items->total(),
			],
		]);
	}

	/**
	 * Accept an item (admin only).
	 */
	public function accept(Request $request, Item $item): JsonResponse
	{
		Gate::authorize('moderate', Item::class);

			$acceptedItem = $this->itemService->acceptItem($item, $request->user());

			return response()->json([
				'message' => 'Item aceptado correctamente.',
				'data' => new ItemResource($acceptedItem),
			]);
	}

	/**
	 * Reject an item (admin only).
	 */
	public function reject(Request $request, Item $item): JsonResponse
	{
		Gate::authorize('moderate', Item::class);

		$request->validate([
			'reason' => ['nullable', 'string', 'max:500'],
		]);


			$rejectedItem = $this->itemService->rejectItem(
				$item,
				$request->user(),
				$request->input('reason')
			);

			return response()->json([
				'message' => 'Item rechazado.',
				'data' => new ItemResource($rejectedItem),
			]);

	}

	/**
	 * Force delete an item (admin only).
	 */
	public function forceDestroy(Item $item): JsonResponse
	{
		Gate::authorize('moderate', Item::class);

			$this->itemService->forceDeleteItem($item);

			return response()->json([
				'message' => 'Item eliminado permanentemente.',
			]);
	}
}