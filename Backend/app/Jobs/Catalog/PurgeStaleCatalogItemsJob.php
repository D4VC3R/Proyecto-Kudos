<?php

namespace App\Jobs\Catalog;

use App\Catalog\Providers\AbstractTmdbProvider;
use App\Contracts\Media\MediaStorageInterface;
use App\Models\Item;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Red de seguridad de los términos de TMDB (pipeline §5): un ítem de TMDB sin refrescar en más de
 * catalog.tmdb_max_age_days días pasa a inactivo, pierde los datos procedentes de TMDB (sinopsis de TMDB,
 * atributos y popularidad) y se borran sus imágenes de TMDB del disco y de `images`. Conserva el nombre,
 * el título original, la fecha, el slug y las fuentes (identifican el ítem, que puede tener votos), y lo que
 * no viene de TMDB (descripción de Wikipedia/Wikidata o manual, imágenes propias). Incluye los ítems borrados.
 * Lo encola catalog:purge-stale. Cola 'catalog'.
 */
class PurgeStaleCatalogItemsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** @param  list<string>  $itemIds */
    public function __construct(public array $itemIds)
    {
        $this->onQueue('catalog');
    }

    public function handle(MediaStorageInterface $storage): void
    {
        $maxAge = (int) config('catalog.tmdb_max_age_days', 180);
        $cutoff = now()->subDays($maxAge);

        $items = Item::withTrashed()
            ->whereKey($this->itemIds)
            ->whereHas('sources', fn ($q) => $q->where('provider', AbstractTmdbProvider::PROVIDER_KEY))
            ->get();

        foreach ($items as $item) {
            // Se comprueba otra vez: puede haberse refrescado entre el comando y el job.
            if ($item->last_synced_at !== null && $item->last_synced_at->greaterThanOrEqualTo($cutoff)) {
                continue;
            }

            $this->purge($item, $storage, $maxAge);
        }
    }

    private function purge(Item $item, MediaStorageInterface $storage, int $maxAge): void
    {
        $directory = 'items/'.($item->category()->value('slug') ?? 'general').'/'.$item->id;

        $kept = [];
        $deleted = 0;

        foreach (array_values(array_filter((array) $item->images, 'is_array')) as $entry) {
            if (! $this->isTmdbImage($entry)) {
                $kept[] = $entry;

                continue;
            }

            $deleted++;

            // Solo se borran los archivos de este ítem: nunca los de semillas o propuestas.
            foreach ((array) ($entry['variants'] ?? []) as $path) {
                if (is_string($path) && str_starts_with($path, "{$directory}/")) {
                    $storage->delete($entry['disk'] ?? 'public', $path);
                }
            }
        }

        if ($deleted > 0) {
            foreach ($kept as $index => $entry) {
                $kept[$index]['order'] = $index;
            }
        }

        $item->images = $kept;
        $item->status = Item::STATUS_INACTIVE;
        // setAttribute: $item->attributes es la propiedad interna de Eloquent.
        $item->setAttribute('attributes', []);
        $item->popularity = 0;

        if ($item->description_source === AbstractTmdbProvider::PROVIDER_KEY) {
            $item->description = null;
            $item->description_source = null;
            $item->description_url = null;
        }

        // Si no hay nada que cambiar (ya purgado), no se guarda ni se registra: el job es idempotente.
        if (! $item->isDirty()) {
            return;
        }

        $item->save(); // el hook saving recalcula quality_score

        Log::warning('Catálogo: ítem de TMDB sin refrescar en más de '.$maxAge.' días; se desactiva y se vacían sus datos de TMDB.', [
            'item_id' => $item->id,
            'last_synced_at' => $item->last_synced_at?->toIso8601String(),
            'images_deleted' => $deleted,
        ]);
    }

    /** @param  array<string, mixed>  $entry */
    private function isTmdbImage(array $entry): bool
    {
        $source = $entry['source_url'] ?? null;

        return is_string($source) && str_starts_with($source, AbstractTmdbProvider::SITE_URL.'/');
    }
}
