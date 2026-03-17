<?php

namespace App\Http\Controllers;

use App\Actions\Admin\Items\ModerateItemStatusAction;
use App\Actions\Admin\Items\UpdateAdminItemAction;
use App\Http\Requests\AdminUpdateItemRequest;
use App\Http\Requests\ListAdminItemsRequest;
use App\Http\Requests\ModerateItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Queries\Admin\Items\ListAdminItemsQuery;
use Illuminate\Http\JsonResponse;

class AdminItemController extends Controller
{
    public function __construct(
        protected ListAdminItemsQuery $listAdminItemsQuery,
        protected UpdateAdminItemAction $updateAdminItemAction,
        protected ModerateItemStatusAction $moderateItemStatusAction,
    ) {
    }

    public function index(ListAdminItemsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = [
            'status' => $validated['status'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'creator_id' => $validated['creator_id'] ?? null,
            'search' => $validated['search'] ?? null,
        ];

        $items = $this->listAdminItemsQuery->execute(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 20),
        );

        return $this->respondList(
            data: ItemResource::collection($items),
            meta: [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        );
    }

    public function update(AdminUpdateItemRequest $request, Item $item): JsonResponse
    {
        $admin = $request->user();

        $payload = $request->validated();
        $reason = $payload['moderation_reason'] ?? null;
        unset($payload['moderation_reason']);

        $updated = $this->updateAdminItemAction->execute($admin, $item, $payload, $reason);

        return $this->respondMutation('Item actualizado por administración.', new ItemResource($updated));
    }

    public function moderate(ModerateItemRequest $request, Item $item): JsonResponse
    {
        $admin = $request->user();

        $status = $request->validated()['status'];
        $reason = $request->validated()['reason'] ?? null;

        $updated = $this->moderateItemStatusAction->execute($admin, $item, $status, $reason);

        return $this->respondMutation('Estado del item actualizado por administración.', new ItemResource($updated));
    }
}

