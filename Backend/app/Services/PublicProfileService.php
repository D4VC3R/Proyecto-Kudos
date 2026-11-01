<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemComment;
use App\Models\User;
use App\Models\UserList;
use App\Models\Vote;
use Illuminate\Support\Collection;

/**
 * Datos del perfil público (F3-03): los mejores ítems por categoría, las listas públicas y las reseñas recientes
 * de un usuario. Los límites salen de config('kudos.profiles').
 */
class PublicProfileService
{
    /**
     * Los mejores ítems del usuario por categoría: sus votos con nota, los `top_per_category` más altos de cada
     * categoría activa (ítems activos y sin borrar). Empata por el voto más reciente. Las categorías salen por
     * `sort_order` y luego por nombre.
     *
     * @param  User  $user  Dueño del perfil.
     * @param  User|null  $viewer  Usuario con sesión, para cargar su voto en cada ítem (`userVote`).
     * @return Collection<int, array{category: Category, votes: Collection<int, Vote>}>
     */
    public function topByCategory(User $user, ?User $viewer): Collection
    {
        $limit = (int) config('kudos.profiles.top_per_category', 5);

        $ranked = Vote::query()
            ->select('votes.*')
            ->selectRaw('categories.sort_order AS category_sort_order, categories.name AS category_name')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY items.category_id ORDER BY votes.score DESC, votes.updated_at DESC, votes.id) AS category_rank')
            ->join('items', 'items.id', '=', 'votes.item_id')
            ->join('categories', 'categories.id', '=', 'items.category_id')
            ->where('votes.user_id', $user->id)
            ->where('votes.type', Vote::TYPE_VOTE)
            ->whereNotNull('votes.score')
            ->where('items.status', Item::STATUS_ACTIVE)
            ->whereNull('items.deleted_at')
            ->where('categories.is_active', true);

        return Vote::query()
            ->fromSub($ranked, 'votes')
            ->where('category_rank', '<=', $limit)
            ->orderBy('category_sort_order')
            ->orderBy('category_name')
            ->orderBy('category_rank')
            ->with(['item' => fn ($query) => $query->with('category')
                ->when($viewer, fn ($q) => $q->with(['userVote' => fn ($vote) => $vote->where('user_id', $viewer->id)]))])
            ->get()
            ->groupBy(fn (Vote $vote) => $vote->item->category_id)
            ->map(fn (Collection $votes) => ['category' => $votes->first()->item->category, 'votes' => $votes->values()])
            ->values();
    }

    /**
     * Listas del usuario que se muestran en su perfil: solo las de visibilidad `public`, sin las editoriales ni las
     * borradas (una `unlisted` no sale en listados y una ocultada por moderación ya es `private`). Las más
     * recientes primero, hasta `lists_limit`.
     *
     * @param  User  $user  Dueño del perfil.
     * @return Collection<int, UserList>
     */
    public function publicLists(User $user): Collection
    {
        return UserList::query()
            ->where('user_id', $user->id)
            ->where('is_editorial', false)
            ->publiclyVisible()
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit((int) config('kudos.profiles.lists_limit', 20))
            ->get();
    }

    /**
     * Últimas reseñas del usuario (comentarios ligados a un voto, sin respuestas ni ocultos) de ítems activos de
     * categorías activas, hasta `reviews_limit`.
     *
     * @param  User  $user  Dueño del perfil.
     * @return Collection<int, ItemComment>
     */
    public function recentReviews(User $user): Collection
    {
        return ItemComment::query()
            ->where('user_id', $user->id)
            ->whereNotNull('vote_id')
            ->whereNull('parent_id')
            ->where('is_hidden', false)
            ->whereHas('item', fn ($q) => $q->active()->whereHas('category', fn ($c) => $c->where('is_active', true)))
            ->with(['vote:id,type,score', 'item' => fn ($q) => $q->with('category')])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit((int) config('kudos.profiles.reviews_limit', 10))
            ->get();
    }
}
