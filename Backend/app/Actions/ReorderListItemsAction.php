<?php

namespace App\Actions;

use App\Models\ListItem;
use App\Models\UserList;
use Illuminate\Support\Facades\DB;

/**
 * Reordena los ítems de una lista según el orden recibido.
 */
class ReorderListItemsAction
{
    /**
     * Bloquea la lista y asigna las posiciones 1..n según el orden de $itemIds.
     * El Form Request ya ha comprobado que son todos los ítems de la lista, una vez cada uno.
     *
     * @param  UserList  $list  Lista a reordenar.
     * @param  array<int, string>  $itemIds  IDs de los ítems en el nuevo orden.
     */
    public function execute(UserList $list, array $itemIds): void
    {
        DB::transaction(function () use ($list, $itemIds) {
            UserList::query()->whereKey($list->getKey())->lockForUpdate()->first();

            foreach (array_values($itemIds) as $index => $itemId) {
                ListItem::query()
                    ->where('list_id', $list->id)
                    ->where('item_id', $itemId)
                    ->update(['position' => $index + 1]);
            }

            $list->touch();
        });
    }
}
