<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Services\ItemService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
		$this->authorize('create', Item::class);

		try {
			$item = $this->itemService->createItem(
				$request->validated(),
				$request->user()
			);

			return response()->json([
				'message' => 'Item propuesto exitosamente. Será revisado por un administrador.',
				'data' => new ItemResource($item),
			], 201);

		} catch (\Exception $e) {
			return response()->json([
				'message' => 'Error al crear el item.',
				'error' => $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Display the specified item.
	 */
	public function show(Request $request, Item $item): JsonResponse
	{

		// Autorizar visualización
		$this->authorize('view', $item);
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
		try {
			$updatedItem = $this->itemService->updateItem($item, $request->validated());

			return response()->json([
				'message' => 'Item actualizado exitosamente.',
				'data' => new ItemResource($updatedItem),
			]);

		} catch (\Exception $e) {
			return response()->json([
				'message' => $e->getMessage(),
			], 422);
		}
	}

	/**
	 * Remove the specified item (only if pending).
	 */
	public function destroy(Item $item): JsonResponse
	{
		$this->authorize('delete', $item);

		try {
			$this->itemService->deleteItem($item);

			return response()->json([
				'message' => 'Item eliminado exitosamente.',
			]);

		} catch (\Exception $e) {
			return response()->json([
				'message' => $e->getMessage(),
			], 422);
		}
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
		$this->authorize('moderate', Item::class);

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
		$this->authorize('moderate', Item::class);

		try {
			$acceptedItem = $this->itemService->acceptItem($item, $request->user());

			return response()->json([
				'message' => 'Item aceptado exitosamente.',
				'data' => new ItemResource($acceptedItem),
			]);

		} catch (\Exception $e) {
			return response()->json([
				'message' => $e->getMessage(),
			], 422);
		}
	}

	/**
	 * Reject an item (admin only).
	 */
	public function reject(Request $request, Item $item): JsonResponse
	{
		$this->authorize('moderate', Item::class);

		$request->validate([
			'reason' => ['nullable', 'string', 'max:500'],
		]);

		try {
			$rejectedItem = $this->itemService->rejectItem(
				$item,
				$request->user(),
				$request->input('reason')
			);

			return response()->json([
				'message' => 'Item rechazado.',
				'data' => new ItemResource($rejectedItem),
			]);

		} catch (\Exception $e) {
			return response()->json([
				'message' => $e->getMessage(),
			], 422);
		}
	}

	/**
	 * Force delete an item (admin only).
	 */
	public function forceDestroy(Item $item): JsonResponse
	{
		$this->authorize('forceDelete', $item);

		try {
			$this->itemService->forceDeleteItem($item);

			return response()->json([
				'message' => 'Item eliminado permanentemente.',
			]);

		} catch (\Exception $e) {
			return response()->json([
				'message' => 'Error al eliminar el item.',
				'error' => $e->getMessage(),
			], 500);
		}
	}
}