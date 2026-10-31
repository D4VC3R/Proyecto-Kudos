<?php

namespace App\Http\Controllers\Admin;

use App\Actions\AddItemToListAction;
use App\Actions\RemoveItemFromListAction;
use App\Actions\ReorderListItemsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lists\AddEditorialListItemRequest;
use App\Http\Requests\Lists\DeleteEditorialListRequest;
use App\Http\Requests\Lists\ListEditorialListsRequest;
use App\Http\Requests\Lists\RemoveEditorialListItemRequest;
use App\Http\Requests\Lists\ReorderEditorialListItemsRequest;
use App\Http\Requests\Lists\ShowEditorialListRequest;
use App\Http\Requests\Lists\StoreEditorialListRequest;
use App\Http\Requests\Lists\UpdateEditorialListRequest;
use App\Http\Resources\UserListResource;
use App\Models\ListItem;
use App\Models\UserList;
use App\Services\HomeFeedService;
use Illuminate\Http\JsonResponse;

/**
 * Gestión de las listas editoriales por parte del administrador (F2-09).
 * Cada escritura borra la caché de la portada (HomeFeedService::forgetCache).
 */
class AdminListController extends Controller
{
    public function __construct(
        private readonly AddItemToListAction $addItem,
        private readonly RemoveItemFromListAction $removeItem,
        private readonly ReorderListItemsAction $reorderItems,
    ) {}

    /**
     * GET /api/admin/lists: las listas editoriales, las editadas más recientemente primero.
     */
    public function index(ListEditorialListsRequest $request): JsonResponse
    {
        $lists = UserList::query()->editorial()->orderByDesc('updated_at')->orderByDesc('id')->get();

        return $this->respondData(UserListResource::collection($lists));
    }

    /**
     * POST /api/admin/lists: crea una lista editorial, siempre pública.
     */
    public function store(StoreEditorialListRequest $request): JsonResponse
    {
        $list = UserList::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'is_editorial' => true,
            'visibility' => UserList::VISIBILITY_PUBLIC,
        ]);

        HomeFeedService::forgetCache();

        return $this->respondMutation('Lista creada correctamente.', new UserListResource($list->fresh()), status: 201);
    }

    /**
     * GET /api/admin/lists/{list}: la lista con todas sus entradas, también las de ítems inactivos o borrados.
     */
    public function show(ShowEditorialListRequest $request, UserList $list): JsonResponse
    {
        return $this->respondData(new UserListResource($this->loadForAdmin($list)));
    }

    /**
     * PUT /api/admin/lists/{list}: edita título, descripción, numeración o portada. El slug no cambia.
     */
    public function update(UpdateEditorialListRequest $request, UserList $list): JsonResponse
    {
        $list->update($request->validated());

        HomeFeedService::forgetCache();

        return $this->respondMutation('Lista actualizada correctamente.', new UserListResource($this->loadForAdmin($list->fresh())));
    }

    /**
     * DELETE /api/admin/lists/{list}: borrado suave.
     */
    public function destroy(DeleteEditorialListRequest $request, UserList $list): JsonResponse
    {
        $list->delete();

        HomeFeedService::forgetCache();

        return $this->respondMutation('Lista eliminada correctamente.');
    }

    /**
     * POST /api/admin/lists/{list}/items: añade un ítem al final.
     */
    public function addItem(AddEditorialListItemRequest $request, UserList $list): JsonResponse
    {
        $validated = $request->validated();

        $this->addItem->execute($list, $validated['item_id'], $validated['note'] ?? null);

        HomeFeedService::forgetCache();

        return $this->respondMutation('Ítem añadido a la lista.', new UserListResource($this->loadForAdmin($list->fresh())), status: 201);
    }

    /**
     * DELETE /api/admin/lists/{list}/items/{itemId}: quita un ítem y renumera las posiciones.
     */
    public function removeItem(RemoveEditorialListItemRequest $request, UserList $list, string $itemId): JsonResponse
    {
        $entry = ListItem::query()->where('list_id', $list->id)->where('item_id', $itemId)->firstOrFail();

        $this->removeItem->execute($list, $entry);

        HomeFeedService::forgetCache();

        return $this->respondMutation('Ítem quitado de la lista.', new UserListResource($this->loadForAdmin($list->fresh())));
    }

    /**
     * PUT /api/admin/lists/{list}/items/order: reordena. Hay que enviar todos los ítems de la lista.
     */
    public function reorder(ReorderEditorialListItemsRequest $request, UserList $list): JsonResponse
    {
        $this->reorderItems->execute($list, $request->validated('item_ids'));

        HomeFeedService::forgetCache();

        return $this->respondMutation('Orden actualizado.', new UserListResource($this->loadForAdmin($list->fresh())));
    }

    /**
     * Carga las entradas con su ítem. El admin ve también los inactivos y los borrados para poder quitarlos
     * y para que el reordenado cuadre con todas las filas.
     */
    private function loadForAdmin(UserList $list): UserList
    {
        return $list->load(['listItems.item' => fn ($query) => $query->withTrashed()->with('category')]);
    }
}
