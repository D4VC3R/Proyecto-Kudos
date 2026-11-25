<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Support\Collection;

/**
 * Recomendaciones por contenido "Porque te gustó X" (F2-08).
 */
class RecommendationService
{
    public const STRATEGY_CONTENT = 'content';
    public const STRATEGY_POPULAR = 'popular';

    public function __construct(
        private readonly SimilarItemsService $similarItems,
        private readonly OnboardingItemsService $onboardingItems
    ) {}

    /**
     * Filas de recomendaciones de un usuario. Parte de sus últimas valoraciones (votes.updated_at) con nota
     * >= catalog.recommendations.min_score (hasta 'seeds', solo de ítems visibles): cada una genera una fila con
     * sus similares sin voto del usuario (de ningún tipo), sin repetir ítems entre filas y omitiendo las filas
     * vacías. Con menos de 'min_liked' valoraciones altas, o si ninguna fila tiene ítems, devuelve una única
     * fila de populares de sus intereses con because = null.
     *
     * @return array{strategy: string, groups: array<int, array{because: Item|null, items: Collection<int, Item>}>}
     */
    public function forUser(User $user): array
    {
        $liked = Vote::query()
            ->where('user_id', $user->id)
            ->where('type', Vote::TYPE_VOTE)
            ->where('score', '>=', (float) config('catalog.recommendations.min_score', 8))
            // Solo ítems visibles: activos, sin borrar y de categorías activas
            ->whereHas('item', fn ($query) => $query->visible());

        if ((clone $liked)->count() < (int) config('catalog.recommendations.min_liked', 3)) {
            return $this->popular($user);
        }

        $seedVotes = (clone $liked)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit((int) config('catalog.recommendations.seeds', 5))
            ->with(['item.category'])
            ->get();

        $perGroup = (int) config('catalog.recommendations.items_per_group', 12);
        $shown = [];
        $groups = [];

        foreach ($seedVotes as $vote) {
            $seed = $vote->item;
            // user_vote del "porque te gustó" = la valoración del usuario
            $seed->setRelation('userVote', $vote);

            // Similares no votados (ningún tipo) y sin repetir los de filas anteriores
            $items = $this->similarItems->eligibleItems($seed, $perGroup, $user, $shown, excludeVotedByViewer: true);

            if ($items->isEmpty()) {
                continue;
            }

            $shown = array_merge($shown, $items->pluck('id')->all());
            $groups[] = ['because' => $seed, 'items' => $items];
        }

        return $groups === [] ? $this->popular($user) : ['strategy' => self::STRATEGY_CONTENT, 'groups' => $groups];
    }

    /**
     * Fila de populares (fallback): los de las categorías activas de sus intereses o, sin ellas, de todas las
     * categorías activas. Reutiliza el reparto del onboarding (OnboardingItemsService::popularItems).
     *
     * @return array{strategy: string, groups: array<int, array{because: Item|null, items: Collection<int, Item>}>}
     */
    private function popular(User $user): array
    {
        $interests = $user->profile()->first()?->interests ?? [];
        $slugs = Category::query()->whereIn('slug', is_array($interests) ? $interests : [])->active()->pluck('slug')->all();

        // Sin intereses (o todos inactivos): populares de todas las categorías activas
        if ($slugs === []) {
            $slugs = Category::query()->active()->pluck('slug')->all();
        }

        $items = $this->onboardingItems->popularItems($user, $slugs, (int) config('catalog.recommendations.popular_limit', 12));

        return [
            'strategy' => self::STRATEGY_POPULAR,
            'groups' => $items->isEmpty() ? [] : [['because' => null, 'items' => $items]],
        ];
    }
}
