<?php

namespace App\Actions;

use App\Models\User;
use App\Models\UserList;
use Illuminate\Support\Facades\DB;

/**
 * Quita el "me gusta" de una lista (F3-01): idempotente, mantiene `likes_count`.
 */
class UnlikeListAction
{
    /**
     * Bloquea la lista, borra el "me gusta" (si existía) y recalcula el contador.
     *
     * @param  UserList  $list  Lista a la que se quita.
     * @param  User  $user  Usuario que quita su "me gusta".
     * @return array{liked: bool, likes_count: int}
     */
    public function execute(UserList $list, User $user): array
    {
        return DB::transaction(function () use ($list, $user) {
            UserList::query()->whereKey($list->getKey())->lockForUpdate()->first();

            DB::table('list_likes')->where('user_id', $user->id)->where('list_id', $list->id)->delete();

            $count = DB::table('list_likes')->where('list_id', $list->id)->count();
            // Con DB::table para no tocar updated_at.
            DB::table('lists')->where('id', $list->id)->update(['likes_count' => $count]);

            return ['liked' => false, 'likes_count' => $count];
        });
    }
}
