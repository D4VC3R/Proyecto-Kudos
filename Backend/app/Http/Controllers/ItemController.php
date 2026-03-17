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
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'No se pudo obtener el usuario autenticado.'], 500);
        }

        $item = $this->createItemAction->execute($request->validated(), $user);

        return response()->json([
            'message' => 'Item creado correctamente.',
            'data' => new ItemResource($item),
        ], 201);
    }

    /**
     * Display the specified item.
     */
    public function show(ShowItemRequest $request, Item $item): JsonResponse
    {
        $item->load(['category', 'creator', 'tags']);

        return response()->json([
            'data' => new ItemResource($item),
        ]);
    }

    /**
     * Update the specified item.
     */
    public function update(UpdateItemRequest $request, Item $item): JsonResponse
    {
        $updatedItem = $this->updateItemAction->execute($item, $request->validated());

        return response()->json([
            'message' => 'Item actualizado correctamente.',
            'data' => new ItemResource($updatedItem),
        ]);
    }

    /**
     * Remove the specified item.
     */
    public function destroy(DeleteItemRequest $request, Item $item): JsonResponse
    {
        $this->deleteItemAction->execute($item);

        return response()->json([
            'message' => 'Item eliminado correctamente.',
        ]);
    }

    /**
     * Get items created by the authenticated user.
     */
    public function myItems(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'No se pudo obtener el usuario autenticado.'], 500);
        }

        $items = $this->listMyItemsQuery->execute($user);

        return response()->json([
            'data' => ItemResource::collection($items),
            'meta' => [
                'total' => $items->count(),
                'active' => $items->where('status', Item::STATUS_ACTIVE)->count(),
                'inactive' => $items->where('status', Item::STATUS_INACTIVE)->count(),
            ],
        ]);
    }
}
