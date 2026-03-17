<?php

namespace App\Http\Controllers;

use App\Actions\Items\CreateItemAction;
use App\Actions\Items\DeleteItemAction;
use App\Actions\Items\UpdateItemAction;
use App\Http\Requests\DeleteItemRequest;
use App\Http\Requests\ShowItemRequest;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Queries\Items\ListActiveItemsQuery;
use App\Queries\Items\ListMyItemsQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function __construct(
        protected ListActiveItemsQuery $listActiveItemsQuery,
        protected ListMyItemsQuery $listMyItemsQuery,
        protected CreateItemAction $createItemAction,
        protected UpdateItemAction $updateItemAction,
        protected DeleteItemAction $deleteItemAction,
    ) {
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

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $items = $this->listActiveItemsQuery->execute($filters, $perPage);

        return $this->respondList(
            data: ItemResource::collection($items),
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

    /**
     * Store a newly created item.
     */
    public function store(StoreItemRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->respondMutation('No se pudo obtener el usuario autenticado.', status: 500);
        }

        $item = $this->createItemAction->execute($request->validated(), $user);

        return $this->respondMutation('Item creado correctamente.', new ItemResource($item), status: 201);
    }

    /**
     * Display the specified item.
     */
    public function show(ShowItemRequest $request, Item $item): JsonResponse
    {
        $item->load(['category', 'creator', 'tags']);

        return $this->respondData(new ItemResource($item));
    }

    /**
     * Update the specified item.
     */
    public function update(UpdateItemRequest $request, Item $item): JsonResponse
    {
        $updatedItem = $this->updateItemAction->execute($item, $request->validated());

        return $this->respondMutation('Item actualizado correctamente.', new ItemResource($updatedItem));
    }

    /**
     * Remove the specified item.
     */
    public function destroy(DeleteItemRequest $request, Item $item): JsonResponse
    {
        $this->deleteItemAction->execute($item);

        return $this->respondMutation('Item eliminado correctamente.');
    }

    /**
     * Get items created by the authenticated user.
     */
    public function myItems(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->respondMutation('No se pudo obtener el usuario autenticado.', status: 500);
        }

        $items = $this->listMyItemsQuery->execute($user);

        return $this->respondList(
            data: ItemResource::collection($items),
            meta: [
                'total' => $items->count(),
                'active' => $items->where('status', Item::STATUS_ACTIVE)->count(),
                'inactive' => $items->where('status', Item::STATUS_INACTIVE)->count(),
            ],
        );
    }
}
