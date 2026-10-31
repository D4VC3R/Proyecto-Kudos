<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Item;
use App\Models\ListItem;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Secciones de la portada (F2-09, GET /api/home).
 *
 * La parte que no depende del usuario (populares, novedades, listas editoriales y mejor valorados) se
 * cachea como IDs, no como JSON: ItemListResource lleva el voto del usuario. En cada petición se
 * hidratan los ítems, descartando los que hayan dejado de estar activos durante la caché.
 */
class HomeFeedService
{
    public const CACHE_KEY = 'home:anonymous';

    public function __construct(
        private readonly RecommendationService $recommendations,
        private readonly OnboardingItemsService $onboardingItems,
    ) {}

    /**
     * Borra la caché de la parte anónima (se llama tras cada escritura del admin en las listas editoriales).
     */
    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Secciones de la portada para un visitante (null) o un usuario con sesión.
     *
     * @param  User|null  $viewer  Usuario con sesión o null.
     * @return array{
     *     for_you: array{strategy: string, groups: array<int, array{because: Item|null, items: Collection<int, Item>}>},
     *     new_releases: Collection<int, Item>,
     *     editorial_lists: Collection<int, UserList>,
     *     top_rated: Collection<int, Item>,
     *     categories: Collection<int, Category>
     * }
     */
    public function forViewer(?User $viewer): array
    {
        $ids = $this->anonymousIds();

        if ($viewer) {
            $forYou = $this->recommendations->forUser($viewer);
        } else {
            $items = $this->hydrateItems($ids['popular'], null);
            $forYou = [
                'strategy' => RecommendationService::STRATEGY_POPULAR,
                'groups' => $items->isEmpty() ? [] : [['because' => null, 'items' => $items]],
            ];
        }

        return [
            'for_you' => $forYou,
            'new_releases' => $this->hydrateItems($ids['new_releases'], $viewer),
            'editorial_lists' => $this->hydrateEditorialLists($ids['editorial_lists'], $viewer),
            'top_rated' => $this->hydrateItems($ids['top_rated'], $viewer),
            // Sin caché: es una consulta barata y así el recuento coincide con GET /categories.
            'categories' => Category::withItemCount()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ];
    }

    /**
     * IDs de las secciones que no dependen del usuario, cacheados catalog.home.cache_minutes minutos.
     *
     * @return array{popular: array<int, string>, new_releases: array<int, string>, top_rated: array<int, string>, editorial_lists: array<int, array{id: string, item_ids: array<int, string>}>}
     */
    private function anonymousIds(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes((int) config('catalog.home.cache_minutes', 10)),
            function () {
                $limit = (int) config('catalog.home.section_limit', 12);
                $past = (int) config('catalog.home.new_releases_past_days', 60);
                $future = (int) config('catalog.home.new_releases_future_days', 30);
                $minVotes = (int) config('catalog.home.top_rated_min_votes', 3);
                $maxLists = (int) config('catalog.home.editorial_lists', 4);
                $perList = (int) config('catalog.home.items_per_list', 6);

                $lists = UserList::query()
                    ->editorial()
                    ->publiclyVisible()
                    ->whereHas('visibleListItems')
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->limit($maxLists)
                    ->get(['id']);

                return [
                    'popular' => $this->onboardingItems->popularItems(
                        null,
                        Category::query()->where('is_active', true)->pluck('slug')->all(),
                        (int) config('catalog.recommendations.popular_limit', 12),
                    )->pluck('id')->all(),
                    'new_releases' => $this->discoverableItems()
                        ->whereBetween('released_at', [today()->subDays($past)->toDateString(), today()->addDays($future)->toDateString()])
                        ->orderByDesc('popularity')
                        ->orderByDesc('released_at')
                        ->orderBy('id')
                        ->limit($limit)
                        ->pluck('id')
                        ->all(),
                    'top_rated' => $this->discoverableItems()
                        ->where('vote_count', '>=', $minVotes)
                        ->orderByDesc('bayes_score')
                        ->orderByDesc('vote_count')
                        ->orderBy('id')
                        ->limit($limit)
                        ->pluck('id')
                        ->all(),
                    'editorial_lists' => $lists->map(fn (UserList $list) => [
                        'id' => $list->id,
                        'item_ids' => $list->visibleListItems()->limit($perList)->pluck('item_id')->all(),
                    ])->all(),
                ];
            }
        );
    }

    /**
     * Ítems activos, descubribles y de una categoría activa.
     *
     * @return Builder<Item>
     */
    private function discoverableItems(): Builder
    {
        return Item::query()
            ->active()
            ->discoverable()
            ->whereHas('category', fn ($category) => $category->where('is_active', true));
    }

    /**
     * Carga los ítems por ID respetando el orden de la caché y descartando los desactivados o borrados.
     * Con $viewer, carga su voto para que ItemListResource dé user_vote y can_vote.
     *
     * @param  array<int, string>  $ids
     * @return Collection<int, Item>
     */
    private function hydrateItems(array $ids, ?User $viewer): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $byId = Item::query()->whereIn('id', $ids)->active()
            ->whereHas('category', fn ($category) => $category->where('is_active', true))
            ->with(['category'])
            ->when($viewer, fn ($query) => $query->with(['userVote' => fn ($vote) => $vote->where('user_id', $viewer->id)]))
            ->get()->keyBy('id');

        return collect($ids)->map(fn (string $id) => $byId->get($id))->filter()->values();
    }

    /**
     * Carga las listas editoriales cacheadas con sus primeros ítems (relación listItems con item).
     * Omite las listas que ya no son públicas o se han quedado sin ítems visibles.
     *
     * @param  array<int, array{id: string, item_ids: array<int, string>}>  $cached
     * @return Collection<int, UserList>
     */
    private function hydrateEditorialLists(array $cached, ?User $viewer): Collection
    {
        if ($cached === []) {
            return collect();
        }

        $listIds = array_column($cached, 'id');
        $lists = UserList::query()->editorial()->publiclyVisible()->whereIn('id', $listIds)->get()->keyBy('id');

        $allIds = array_values(array_unique(array_merge(...array_column($cached, 'item_ids'))));
        $items = $this->hydrateItems($allIds, $viewer)->keyBy('id');

        $rows = ListItem::query()
            ->whereIn('list_id', $listIds)
            ->whereIn('item_id', $allIds)
            ->orderBy('position')
            ->get()
            ->groupBy('list_id');

        $result = collect();

        foreach ($cached as $entry) {
            $list = $lists->get($entry['id']);

            if (! $list) {
                continue;
            }

            $entries = ($rows->get($entry['id']) ?? collect())
                ->filter(fn (ListItem $row) => in_array($row->item_id, $entry['item_ids'], true) && $items->has($row->item_id))
                ->each(fn (ListItem $row) => $row->setRelation('item', $items->get($row->item_id)))
                ->values();

            if ($entries->isEmpty()) {
                continue;
            }

            $list->setRelation('listItems', $entries);
            $result->push($list);
        }

        return $result;
    }
}
