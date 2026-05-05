<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\AdminUpdateItemRequest;
use App\Http\Requests\Admin\ListAdminItemsRequest;
use App\Http\Requests\Admin\ModerateItemRequest;
use App\Http\Resources\ItemDetailResource;
use App\Http\Resources\ItemListResource;
use App\Models\Item;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;

class AdminItemController extends Controller
{
    public function __construct(
        protected AdminService $adminService,
    ) {
    }

    // Devuelve todos los items y acepta los filtros de estado, categoría, creador, busqueda, orden y tipo de orden.
    public function index(ListAdminItemsRequest $request): JsonResponse
    {

        $validated = $request->validated();// Validar la petición

        $filters = [ // Comprobar si vienen filtros en la petición.
            'status' => $validated['status'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'creator_id' => $validated['creator_id'] ?? null,
            'search' => $validated['search'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        $items = $this->adminService->listItems( // Recuperar los items
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 20), // Si no recibimos el parametro de paginacion, devolvemos 20 por seguridad.
        );

        return $this->respondList( // Responder con el resultado utilizando el resource de ItemList.
            data: ItemListResource::collection($items),
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

        return $this->respondMutation('Item actualizado por administración.', new ItemDetailResource($updated));
    }

    public function moderate(ModerateItemRequest $request, Item $item): JsonResponse
    {
        $admin = $request->user();

        $status = $request->validated()['status'];
        $reason = $request->validated()['reason'] ?? null;

        $updated = $this->adminService->moderateItemStatus($admin, $item, $status, $reason);

        return $this->respondMutation('Estado del item actualizado por administración.', new ItemDetailResource($updated));
    }
}
