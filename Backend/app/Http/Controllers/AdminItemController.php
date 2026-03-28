<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminUpdateItemRequest;
use App\Http\Requests\ListAdminItemsRequest;
use App\Http\Requests\ModerateItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;

class AdminItemController extends Controller
{
    public function __construct(
        protected AdminService $adminService,
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

        $items = $this->adminService->listItems(
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

        $updated = $this->adminService->updateAdminItem($admin, $item, $payload, $reason);

        return $this->respondMutation('Item actualizado por administración.', new ItemResource($updated));
    }

    public function moderate(ModerateItemRequest $request, Item $item): JsonResponse
    {
        $admin = $request->user();

        $status = $request->validated()['status'];
        $reason = $request->validated()['reason'] ?? null;

        $updated = $this->adminService->moderateItemStatus($admin, $item, $status, $reason);

        return $this->respondMutation('Estado del item actualizado por administración.', new ItemResource($updated));
    }
}
