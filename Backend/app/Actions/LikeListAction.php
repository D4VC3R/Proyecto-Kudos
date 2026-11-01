<?php

namespace App\Actions;

use App\Models\User;
use App\Models\UserList;
use App\Notifications\ListLikedNotification;
use Illuminate\Support\Facades\DB;

/**
 * Da "me gusta" a una lista (F3-01): idempotente, mantiene `likes_count` y avisa al dueño.
 */
class LikeListAction
{
    /**
     * Bloquea la lista, registra el "me gusta" (si no existía) y recalcula el contador.
     *
     * @param  UserList  $list  Lista que gusta.
     * @param  User  $liker  Usuario que da "me gusta".
     * @return array{liked: bool, likes_count: int}
     */
    public function execute(UserList $list, User $liker): array
    {
        return DB::transaction(function () use ($list, $liker) {
            // Bloquea la lista: serializa los "me gusta" simultáneos (recuento y aviso sin duplicados).
            UserList::query()->whereKey($list->getKey())->lockForUpdate()->first();

            $inserted = DB::table('list_likes')->insertOrIgnore([
                'user_id' => $liker->id,
                'list_id' => $list->id,
                'created_at' => now(),
            ]) > 0;

            $count = DB::table('list_likes')->where('list_id', $list->id)->count();
            // Con DB::table para no tocar updated_at: un "me gusta" no es una edición (la portada ordena por updated_at).
            DB::table('lists')->where('id', $list->id)->update(['likes_count' => $count]);

            if ($inserted && ! $list->is_editorial) {
                $this->notifyOwner($list, $liker);
            }

            return ['liked' => true, 'likes_count' => $count];
        });
    }

    /**
     * Avisa al dueño como mucho 1 vez al día (día natural en la zona de la app) por lista: quitar y volver
     * a dar "me gusta" el mismo día no repite el aviso. `data` es `text`, de ahí el `::jsonb`.
     */
    private function notifyOwner(UserList $list, User $liker): void
    {
        $owner = $list->user;

        if ($owner === null || $owner->id === $liker->id) {
            return;
        }

        $alreadyNotified = $owner->notifications()
            ->where('type', ListLikedNotification::class)
            ->whereRaw("data::jsonb ->> 'list_id' = ?", [$list->id])
            ->where('created_at', '>=', now()->startOfDay())
            ->exists();

        if (! $alreadyNotified) {
            $owner->notify(new ListLikedNotification($list, $liker));
        }
    }
}
