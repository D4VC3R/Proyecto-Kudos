<?php

namespace App\Actions;

use App\Models\ListItem;
use App\Models\UserList;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Añade un ítem al final de una lista y actualiza su contador de ítems.
 */
class AddItemToListAction
{
    /**
     * Bloquea la lista, calcula la siguiente posición (1..n sin huecos) y crea la entrada.
     *
     * @param  UserList  $list  Lista a la que se añade.
     * @param  string  $itemId  ID del ítem.
     * @param  string|null  $note  Nota opcional de la entrada.
     * @return ListItem La entrada creada.
     *
     * @throws ValidationException Si la lista ya tiene el máximo de ítems o el ítem ya está en ella
     *                             (carrera de dos altas simultáneas).
     */
    public function execute(UserList $list, string $itemId, ?string $note = null): ListItem
    {
        return DB::transaction(function () use ($list, $itemId, $note) {
            UserList::query()->whereKey($list->getKey())->lockForUpdate()->first();

            // Límite de ítems por lista (F3-01): vale para las listas editoriales y las de usuario.
            $max = (int) config('kudos.lists.max_items', 100);
            if (ListItem::query()->where('list_id', $list->id)->count() >= $max) {
                throw ValidationException::withMessages(['item_id' => "La lista ya tiene el máximo de {$max} ítems."]);
            }

            if (ListItem::query()->where('list_id', $list->id)->where('item_id', $itemId)->exists()) {
                throw ValidationException::withMessages(['item_id' => 'El ítem ya está en la lista.']);
            }

            $position = (int) ListItem::query()->where('list_id', $list->id)->max('position') + 1;

            $entry = ListItem::create([
                'list_id' => $list->id,
                'item_id' => $itemId,
                'position' => $position,
                'note' => $note,
            ]);

            // forceFill + save también actualiza updated_at (orden de las listas de la portada).
            $list->forceFill(['items_count' => ListItem::query()->where('list_id', $list->id)->count()])->save();

            return $entry;
        });
    }
}
