<?php

namespace App\Http\Controllers\Lists;

use App\Actions\AddItemToListAction;
use App\Actions\CreateUserListAction;
use App\Actions\RemoveItemFromListAction;
use App\Actions\ReorderListItemsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lists\AddOwnListItemRequest;
use App\Http\Requests\Lists\DeleteOwnListRequest;
use App\Http\Requests\Lists\ListOwnListsRequest;
use App\Http\Requests\Lists\RemoveOwnListItemRequest;
use App\Http\Requests\Lists\ReorderOwnListItemsRequest;
use App\Http\Requests\Lists\ShowOwnListRequest;
use App\Http\Requests\Lists\StoreOwnListRequest;
use App\Http\Requests\Lists\UpdateOwnListItemNoteRequest;
use App\Http\Requests\Lists\UpdateOwnListRequest;
use App\Http\Resources\UserListResource;
use App\Models\ListItem;
use App\Models\UserList;
use Illuminate\Http\JsonResponse;

/**
 * Listas propias del usuario (F3-01). No llama a HomeFeedService::forgetCache(): en la portada solo salen
 * listas editoriales.
 */
class OwnListController extends Controller
{
    public function __construct(
        private readonly CreateUserListAction $createList,
        private readonly AddItemToListAction $addItem,
        private readonly RemoveItemFromListAction $removeItem,
        private readonly ReorderListItemsAction $reorderItems,
    ) {}

    /**
     * GET /api/profile/lists: las listas propias (no editoriales), las editadas más recientemente primero.
     * Con ?item_id= cada lista indica si ya contiene ese ítem (`contains_item`).
     */
    public function index(ListOwnListsRequest $request): JsonResponse
    {
        $itemId = $request->validated('item_id');

        $lists = UserList::query()
            ->where('user_id', $request->user()->id)
            ->where('is_editorial', false)
            ->when($itemId, fn ($q) => $q->withExists(['listItems as contains_item' => fn ($e) => $e->where('item_id', $itemId)]))
            ->orderByDesc('updated_at')->orderByDesc('id')
            ->get();

        return $this->respondData(UserListResource::collection($lists), [
            'total' => $lists->count(),
            'max_lists' => (int) config('kudos.lists.max_per_user', 50),
            'max_items' => (int) config('kudos.lists.max_items', 100),
        ]);
    }

    /**
     * POST /api/profile/lists: crea una lista propia, privada salvo que se elija otra visibilidad.
     */
    public function store(StoreOwnListRequest $request): JsonResponse
    {
        $list = $this->createList->execute($request->user(), $request->validated());

        return $this->respondMutation('Lista creada correctamente.', new UserListResource($list), status: 201);
    }

    /**
     * GET /api/profile/lists/{list}: la lista con todas sus entradas, también las de ítems inactivos o borrados.
     */
    public function show(ShowOwnListRequest $request, UserList $list): JsonResponse
    {
        return $this->respondData(new UserListResource($this->loadForOwner($list)));
    }

    /**
     * PUT /api/profile/lists/{list}: edita título, descripción, visibilidad, numeración o portada. El slug no cambia.
     */
    public function update(UpdateOwnListRequest $request, UserList $list): JsonResponse
    {
        $list->update($request->validated());

        return $this->respondMutation('Lista actualizada correctamente.', new UserListResource($this->loadForOwner($list->fresh())));
    }

    /**
     * DELETE /api/profile/lists/{list}: borrado suave.
     */
    public function destroy(DeleteOwnListRequest $request, UserList $list): JsonResponse
    {
        $list->delete();

        return $this->respondMutation('Lista eliminada correctamente.');
    }

    /**
     * POST /api/profile/lists/{list}/items: añade un ítem activo al final.
     */
    public function addItem(AddOwnListItemRequest $request, UserList $list): JsonResponse
    {
        $validated = $request->validated();

        $this->addItem->execute($list, $validated['item_id'], $validated['note'] ?? null);

        return $this->respondMutation('Ítem añadido a la lista.', new UserListResource($this->loadForOwner($list->fresh())), status: 201);
    }

    /**
     * PATCH /api/profile/lists/{list}/items/{itemId}: cambia la nota de una entrada (null la borra).
     */
    public function updateItemNote(UpdateOwnListItemNoteRequest $request, UserList $list, string $itemId): JsonResponse
    {
        $entry = ListItem::query()->where('list_id', $list->id)->where('item_id', $itemId)->firstOrFail();

        $entry->update(['note' => $request->validated('note')]);
        $list->touch();

        return $this->respondMutation('Nota guardada.', new UserListResource($this->loadForOwner($list->fresh())));
    }

    /**
     * DELETE /api/profile/lists/{list}/items/{itemId}: quita un ítem y renumera las posiciones.
     */
    public function removeItem(RemoveOwnListItemRequest $request, UserList $list, string $itemId): JsonResponse
    {
        $entry = ListItem::query()->where('list_id', $list->id)->where('item_id', $itemId)->firstOrFail();

        $this->removeItem->execute($list, $entry);

        return $this->respondMutation('Ítem quitado de la lista.', new UserListResource($this->loadForOwner($list->fresh())));
    }

    /**
     * PUT /api/profile/lists/{list}/items/order: reordena. Hay que enviar todos los ítems de la lista.
     */
    public function reorder(ReorderOwnListItemsRequest $request, UserList $list): JsonResponse
    {
        $this->reorderItems->execute($list, $request->validated('item_ids'));

        return $this->respondMutation('Orden actualizado.', new UserListResource($this->loadForOwner($list->fresh())));
    }

    /**
     * Carga las entradas con su ítem. El dueño ve también los inactivos y los borrados para poder quitarlos
     * y para que el reordenado cuadre con todas las filas.
     */
    private function loadForOwner(UserList $list): UserList
    {
        return $list->load(['listItems.item' => fn ($query) => $query->withTrashed()->with('category')]);
    }
}
