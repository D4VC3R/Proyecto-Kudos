<?php

namespace App\Actions;

use App\Models\ListItem;
use App\Models\UserList;
use Illuminate\Support\Facades\DB;

/**
 * Quita un ítem de una lista, renumera las posiciones restantes y actualiza el contador.
 */
class RemoveItemFromListAction
{
    /**
     * Bloquea la lista, borra la entrada y deja las posiciones en 1..n sin huecos.
     *
     * @param  UserList  $list  Lista de la que se quita.
     * @param  ListItem  $entry  Entrada a borrar.
     */
    public function execute(UserList $list, ListItem $entry): void
    {
        DB::transaction(function () use ($list, $entry) {
            UserList::query()->whereKey($list->getKey())->lockForUpdate()->first();

            $entry->delete();

            // Solo se actualizan las filas cuya posición cambia.
            ListItem::query()->where('list_id', $list->id)->orderBy('position')->get()
                ->each(function (ListItem $row, int $index) {
                    if ($row->position !== $index + 1) {
                        $row->update(['position' => $index + 1]);
                    }
                });

            $list->forceFill(['items_count' => ListItem::query()->where('list_id', $list->id)->count()])->save();
        });
    }
}
