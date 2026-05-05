<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\DeleteItemRequest;
use App\Http\Requests\Admin\UpdateItemRequest;
use App\Http\Requests\ListItemsRequest;
use App\Http\Requests\ShowItemRequest;
use App\Http\Requests\StoreItemRequest;
use App\Http\Resources\ItemDetailResource;
use App\Http\Resources\ItemListResource;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
	public function index(ListItemsRequest $request): JsonResponse
	{
		$validated = $request->validated();
		$user = $this->resolveAuthenticatedUser($request);

		$filters = [
			'category_id' => $validated['category_id'] ?? null,
			'search' => $validated['search'] ?? null,
			'exclude_voted_by' => (($validated['sort_by'] ?? '') === 'random' && $user) ? $user->id : null,
		];

		$items = Item::query()
			->active()
			->with(['category:id,name,slug,image,description,created_at,updated_at', 'creator:id,name'])
			->when($user, fn($q) => $q->with(['userVote' => fn($vQ) => $vQ->where('user_id', $user->id)]))
			->applyFilters($filters)
			->applySorting($validated['sort_by'] ?? 'vote_avg', $validated['sort_order'] ?? 'desc')
			->paginate((int) ($validated['per_page'] ?? 15));

		return $this->respondList(
			data: ItemListResource::collection($items),
			meta: [
				'current_page' => $items->currentPage(),
				'last_page' => $items->lastPage(),
				'per_page' => $items->perPage(),
				'total' => $items->total(),
			],
			links: [
				'first' => $items->url(1),
				'last' => $items->url($items->lastPage()),
				'prev' => $items->previousPageUrl(),
				'next' => $items->nextPageUrl(),
			],
		);
	}

	public function store(StoreItemRequest $request): JsonResponse
	{
		$user = $this->resolveAuthenticatedUser($request);

		if (!$user) {
			return $this->respondError('UNAUTHORIZED', 'No se pudo obtener el usuario autenticado.', status: 401);
		}

		$data = array_merge($request->validated(), ['creator_id' => $user->id]);

		$item = Item::create($data)->load(['category', 'creator']);

		return $this->respondMutation('Item creado correctamente.', new ItemDetailResource($item), status: 201);
	}

	public function show(ShowItemRequest $request, Item $item): JsonResponse
	{
		$item->load(['category', 'creator']);

		if ($user = $this->resolveAuthenticatedUser($request)) {
			$item->load(['userVote' => fn ($q) => $q->where('user_id', $user->id)]);
		}

		return $this->respondData(new ItemDetailResource($item));
	}

	public function update(UpdateItemRequest $request, Item $item): JsonResponse
	{
		$item->update($request->validated());

		return $this->respondMutation('Item actualizado correctamente.', new ItemDetailResource($item->fresh(['category', 'creator'])));
	}

	public function destroy(DeleteItemRequest $request, Item $item): JsonResponse
	{
		$item->delete();

		return $this->respondMutation('Item eliminado correctamente.');
	}

	public function myItems(Request $request): JsonResponse
	{
		$user = $this->resolveAuthenticatedUser($request);

		if (!$user) {
			return $this->respondError('UNAUTHORIZED', 'No se pudo obtener el usuario autenticado.', status: 401);
		}

		$items = Item::query()
			->where('creator_id', $user->id)
			->with(['category:id,name,slug,image,description,created_at,updated_at'])
			->when(!$user->hasRole('admin'), fn($q) => $q->active())
			->when($user, fn($q) => $q->with(['userVote' => fn($vQ) => $vQ->where('user_id', $user->id)]))
			->latest()
			->get();

		return $this->respondList(
			data: ItemListResource::collection($items),
			meta: [
				'total' => $items->count(),
				'active' => $items->where('status', Item::STATUS_ACTIVE)->count(),
				'inactive' => $items->where('status', Item::STATUS_INACTIVE)->count(),
			],
		);
	}

	private function resolveAuthenticatedUser(Request $request): ?User
	{
		$requestUser = $request->user();
		if ($requestUser instanceof User) return $requestUser;

		$sanctumUser = Auth::guard('sanctum')->user();
		return $sanctumUser instanceof User ? $sanctumUser : null;
	}
}