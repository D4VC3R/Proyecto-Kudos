<?php

namespace App\Actions;

use App\Models\Item;
use App\Models\ItemComment;
use App\Models\KudosTransaction;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserList;
use App\Models\Vote;
use App\Notifications\CommentRepliedNotification;
use App\Notifications\ListLikedNotification;
use App\Services\ItemStatsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Borra una cuenta y sus datos personales (F3-08, RGPD: derecho de supresión) en una transacción.
 * Los comentarios principales con respuestas de otras personas se anonimizan (user_id = null y texto
 * sustituido); el resto de lo que el usuario creó se borra del todo, también lo de borrado suave.
 * Las medias de los ítems que había valorado y el contador de "me gusta" de las listas se recalculan.
 */
class DeleteAccountAction
{
    public function __construct(protected ItemStatsService $itemStatsService) {}

    /**
     * Borra al usuario y todo lo que cuelga de él. El avatar en disco se borra al final, fuera de la transacción.
     */
    public function execute(User $user): void
    {
        $userId = $user->id;

        DB::transaction(function () use ($user, $userId) {
            // Bloquea al usuario: dos borrados simultáneos no se pisan.
            User::query()->whereKey($userId)->lockForUpdate()->first();

            // Ítems con valoraciones del usuario: se bloquean antes de tocar los votos, como EmitVoteAction (F0-16).
            $ratedItemIds = Vote::query()->where('user_id', $userId)->whereIn('type', Vote::RATED_TYPES)
                ->distinct()->pluck('item_id')->all();
            Item::withTrashed()->whereKey($ratedItemIds)->orderBy('id')->lockForUpdate()->pluck('id');

            // Listas con "me gusta" del usuario: se bloquean para recalcular likes_count (como UnlikeListAction).
            $likedListIds = DB::table('list_likes')->where('user_id', $userId)->pluck('list_id')->all();
            UserList::withTrashed()->whereKey($likedListIds)->orderBy('id')->lockForUpdate()->pluck('id');

            // 1. Tokens, sesiones y enlaces de restablecimiento de contraseña.
            $user->tokens()->delete();
            DB::table('sessions')->where('user_id', $userId)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            // 2. Notificaciones: las suyas y las de otros usuarios que lo nombran.
            $replyIds = ItemComment::withTrashed()->where('user_id', $userId)->whereNotNull('parent_id')->pluck('id')->all();
            $this->deleteNotifications($user, $replyIds);

            // 3. Comentarios. Los principales con respuestas vivas de otros usuarios se anonimizan;
            //    toBase() para no tocar updated_at (no debe salir como "editado").
            $anonymizedIds = ItemComment::query()
                ->where('user_id', $userId)
                ->whereNull('parent_id')
                ->whereHas('replies', fn ($replies) => $replies->where('user_id', '!=', $userId))
                ->pluck('id')->all();
            ItemComment::query()->whereKey($anonymizedIds)->toBase()->update([
                'user_id' => null,
                'content' => ItemComment::ANONYMIZED_CONTENT,
                'vote_id' => null,
            ]);
            //    El resto (también los de borrado suave y sus respuestas propias) se borra del todo.
            //    Las respuestas ajenas a un comentario ya borrado (no visibles) caen por la FK parent_id.
            ItemComment::withTrashed()->where('user_id', $userId)->forceDelete();

            // 4. "Me gusta" dados y recálculo de likes_count de esas listas, sin tocar updated_at.
            DB::table('list_likes')->where('user_id', $userId)->delete();
            if ($likedListIds !== []) {
                DB::table('lists')->whereIn('id', $likedListIds)->update([
                    'likes_count' => DB::raw('(SELECT COUNT(*) FROM list_likes WHERE list_likes.list_id = lists.id)'),
                ]);
            }

            // 5. Listas propias, también las de borrado suave (list_items y sus "me gusta" caen por la FK).
            DB::table('lists')->where('user_id', $userId)->delete();

            // 6. Votos (también want y not_interested: la wishlist) y recálculo de las medias de los ítems vivos.
            Vote::query()->where('user_id', $userId)->delete();
            foreach (Item::query()->whereKey($ratedItemIds)->orderBy('id')->pluck('id') as $itemId) {
                $this->itemStatsService->recalculate($itemId);
            }

            // 7. Transacciones de kudos y perfil.
            KudosTransaction::query()->where('user_id', $userId)->delete();
            Profile::query()->where('user_id', $userId)->delete();

            // 8. Por último, el usuario: HasRoles quita sus roles; la FK borra sus propuestas y deja en null
            //    reports.reporter_id, items.creator_id y el resto de referencias de moderación.
            $user->delete();
        });

        // El avatar, fuera de la transacción: si esta fallara, no se pierde el de una cuenta que sigue existiendo.
        Storage::disk('public')->deleteDirectory('avatars/'.$userId);
    }

    /**
     * Borra las notificaciones del usuario y las de otros que lo nombran: "me gusta" a sus listas
     * (data.liker_id) y respuestas suyas (data.comment_id). `data` es text, de ahí el ::jsonb.
     *
     * @param  array<int, string>  $replyIds  Respuestas escritas por el usuario.
     */
    private function deleteNotifications(User $user, array $replyIds): void
    {
        DB::table('notifications')
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id)
            ->delete();

        DB::table('notifications')
            ->where('type', ListLikedNotification::class)
            ->whereRaw("data::jsonb ->> 'liker_id' = ?", [$user->id])
            ->delete();

        if ($replyIds !== []) {
            DB::table('notifications')
                ->where('type', CommentRepliedNotification::class)
                ->whereIn(DB::raw("data::jsonb ->> 'comment_id'"), $replyIds)
                ->delete();
        }
    }
}
