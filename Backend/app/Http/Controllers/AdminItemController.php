<?php

namespace App\Http\Controllers;

use App\Actions\Admin\Items\ModerateItemStatusAction;
use App\Actions\Admin\Items\UpdateAdminItemAction;
use App\Http\Requests\AdminUpdateItemRequest;
use App\Http\Requests\ModerateItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Models\User;
use App\Queries\Admin\Items\ListAdminItemsQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminItemController extends Controller
{
    public function __construct(
        protected ListAdminItemsQuery $listAdminItemsQuery,
        protected UpdateAdminItemAction $updateAdminItemAction,
        protected ModerateItemStatusAction $moderateItemStatusAction,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->query('status'),
            'category_id' => $request->query('category_id'),
            'creator_id' => $request->query('creator_id'),
            'search' => $request->query('search'),
        ];

        $items = $this->listAdminItemsQuery->execute(
            filters: $filters,
            perPage: (int) $request->query('per_page', 20),
        );

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

    public function update(AdminUpdateItemRequest $request, Item $item): JsonResponse
    {
        $admin = $request->user();
        if (!$admin instanceof User) {
            return response()->json(['message' => 'No se pudo obtener el administrador autenticado.'], 500);
        }

        $payload = $request->validated();
        $reason = $payload['moderation_reason'] ?? null;
        unset($payload['moderation_reason']);

        $updated = $this->updateAdminItemAction->execute($admin, $item, $payload, $reason);

        return response()->json([
            'message' => 'Item actualizado por administración.',
            'data' => new ItemResource($updated),
        ]);
    }

    public function moderate(ModerateItemRequest $request, Item $item): JsonResponse
    {
        $admin = $request->user();
        if (!$admin instanceof User) {
            return response()->json(['message' => 'No se pudo obtener el administrador autenticado.'], 500);
        }

        $status = $request->validated()['status'];
        $reason = $request->validated()['reason'] ?? null;

        $updated = $this->moderateItemStatusAction->execute($admin, $item, $status, $reason);

        return response()->json([
            'message' => 'Estado del item actualizado por administración.',
            'data' => new ItemResource($updated),
        ]);
    }
}

