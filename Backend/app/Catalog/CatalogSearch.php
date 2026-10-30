<?php

namespace App\Catalog;

use App\Catalog\Concerns\CallsProvidersOnDemand;
use App\Catalog\Data\CatalogItemData;
use App\Catalog\Exceptions\CatalogUnavailableException;
use App\Models\Item;
use App\Models\ItemSource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Búsqueda de candidatos en la fuente externa de una categoría (F1-07, pipeline-catalogo §7).
 * Los candidatos se cachean por (categoría, texto); si ya están en Kudos se marcan al devolverlos.
 */
final class CatalogSearch
{
    use CallsProvidersOnDemand;

    public function __construct(private readonly CatalogProviderRegistry $registry) {}

    /**
     * Candidatos de la fuente para (categoría, texto), cacheados catalog.on_demand.search_cache_minutes,
     * marcados con exists e item_id (siempre frescos: no se cachean).
     *
     * @return list<array{external_id: string, name: string, original_title: ?string, released_at: ?string, year: ?int, description: ?string, source_url: ?string, exists: bool, item_id: ?string}>
     *
     * @throws CatalogUnavailableException
     */
    public function search(string $categorySlug, string $query): array
    {
        $provider = $this->registry->forCategory($categorySlug);
        $key = 'catalog:search:'.$categorySlug.':'.md5(Str::lower(Str::squish($query)));

        $candidates = Cache::remember(
            $key,
            now()->addMinutes((int) config('catalog.on_demand.search_cache_minutes', 10)),
            fn () => array_map(
                fn (CatalogItemData $d) => [
                    'external_id' => $d->externalId,
                    'name' => $d->name,
                    'original_title' => $d->originalTitle,
                    'released_at' => $d->releasedAt,
                    'year' => isset($d->attributes['year']) ? (int) $d->attributes['year'] : null,
                    'description' => $d->description,
                    'source_url' => $d->sourceUrl,
                ],
                $this->callProvider(fn () => $provider->search(Str::squish($query))),
            ),
        );

        return $this->markExisting($provider->key(), $candidates);
    }

    /**
     * @param  list<array<string, mixed>>  $candidates
     * @return list<array<string, mixed>>
     */
    private function markExisting(string $providerKey, array $candidates): array
    {
        $ids = array_column($candidates, 'external_id');

        $sources = ItemSource::query()
            ->where('provider', $providerKey)
            ->whereIn('external_id', $ids)
            ->pluck('item_id', 'external_id');

        $items = Item::withTrashed()
            ->whereIn('id', $sources->values()->all())
            ->get(['id', 'status', 'deleted_at'])
            ->keyBy('id');

        return array_map(function (array $candidate) use ($sources, $items) {
            $externalId = $candidate['external_id'];
            $item = isset($sources[$externalId]) ? $items->get($sources[$externalId]) : null;

            $candidate['exists'] = isset($sources[$externalId]);
            $candidate['item_id'] = ($item && ! $item->trashed() && $item->status === Item::STATUS_ACTIVE) ? $item->id : null;

            return $candidate;
        }, $candidates);
    }
}
