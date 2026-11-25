<?php

namespace App\Http\Controllers\Item;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteItemRequest;
use App\Http\Requests\Items\ListItemsRequest;
use App\Http\Requests\Items\ShowItemRequest;
use App\Http\Requests\Items\StoreItemRequest;
use App\Http\Resources\ItemDetailResource;
use App\Http\Resources\ItemListResource;
use App\Models\Item;
use App\Services\ModerationAuditLogger;
use App\Services\SimilarItemsService;
use Illuminate\Http\JsonResponse;

class ItemController extends Controller
{
	public function index(ListItemsRequest $request): JsonResponse
	{
		$validated = $request->validated();
		$user = $request->user();

		$filters = [
			'category_id' => $validated['category_id'] ?? null,
			'category_slug' => $validated['category_slug'] ?? null,
			'search' => $validated['search'] ?? null,
			'discoverable' => (bool) ($validated['discoverable'] ?? false),
			'exclude_voted_by' => (($validated['sort_by'] ?? '') === 'random' && $user) ? $user->id : null,
		];

		$items = Item::query()
			->visible()
			->with(['category:id,name,slug,image,description,attribute_schema,icon,sort_order,created_at,updated_at', 'creator:id,name'])
			->when($user, fn($q) => $q->with(['userVote' => fn($vQ) => $vQ->where('user_id', $user->id)]))
			->applyFilters($filters)
			->applySorting($validated['sort_by'] ?? 'vote_avg', $validated['sort_order'] ?? 'desc')
			->simplePaginate((int) ($validated['per_page'] ?? 15));

		return $this->respondList(
			data: ItemListResource::collection($items),
			meta: [
				'current_page' => $items->currentPage(),
				'per_page' => $items->perPage(),
				'has_more' => $items->hasMorePages(),
			],
			links: [
				'first' => $items->url(1),
				'prev' => $items->previousPageUrl(),
				'next' => $items->nextPageUrl(),
			],
		);
	}

	public function store(StoreItemRequest $request): JsonResponse
	{
		$user = $request->user();

		$data = array_merge($request->validated(), ['creator_id' => $user->id]);

		$item = Item::create($data)->load(['category', 'creator']);

		return $this->respondMutation('Item creado correctamente.', new ItemDetailResource($item), status: 201);
	}

	public function show(ShowItemRequest $request, Item $item): JsonResponse
	{
		$item->load(['category', 'creator']);

		if ($user = $request->user()) {
			$item->load(['userVote' => fn ($q) => $q->where('user_id', $user->id)]);
		}

		return $this->respondData(new ItemDetailResource($item));
	}

	/**
	 * GET /api/items/{item}/similar (F2-08): hasta catalog.similar.limit ítems de la misma categoría por similitud de contenido.
	 * Misma autorización que show() (un ítem inactivo solo lo ve un admin). Con sesión, cada ítem lleva user_vote.
	 */
	public function similar(ShowItemRequest $request, Item $item, SimilarItemsService $similarItems): JsonResponse
	{
		$items = $similarItems->similarTo($item, $request->user());

		return $this->respondData(ItemListResource::collection($items));
	}

	public function destroy(DeleteItemRequest $request, Item $item, ModerationAuditLogger $moderationAuditLogger): JsonResponse
	{
		$item->delete();

		$moderationAuditLogger->logItemModeration($item, $request->user(), 'admin_delete_item');

		return $this->respondMutation('Item eliminado correctamente.');
	}
}

