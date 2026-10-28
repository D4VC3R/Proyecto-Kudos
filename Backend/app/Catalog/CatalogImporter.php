<?php

namespace App\Catalog;

use App\Catalog\Data\CatalogItemData;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemSource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Upsert idempotente de un CatalogItemData en Item + ItemSource (pipeline-catalogo §4, pasos 1, 3, 5 y 6).
 *
 * Orden de búsqueda: 1) la fuente (provider + external_id), 2) el QID de Wikidata dentro de la misma categoría,
 * 3) se crea. Nunca se busca ni se fusiona por nombre. Los ítems nuevos nacen con creator_id = null y
 * status = active; **solo al crear**: al refrescar no se toca el status, así que un ítem desactivado
 * (a mano o por un 404) sigue inactivo. Un ítem borrado (soft delete) no se resucita ni se actualiza.
 * Las imágenes no se descargan aquí: lo hace DownloadItemImagesJob.
 */
final class CatalogImporter
{
    public const WIKIDATA_PROVIDER = 'wikidata';

    public const WIKIDATA_URL = 'https://www.wikidata.org/wiki/';

    /**
     * Crea o actualiza el ítem del DTO y sus fuentes (la del proveedor, primaria, y la de Wikidata si hay QID).
     *
     * @throws ModelNotFoundException si la categoría del DTO no existe
     */
    public function import(CatalogItemData $data): Item
    {
        $category = Category::query()->where('slug', $data->categorySlug)->firstOrFail();

        return DB::transaction(function () use ($data, $category): Item {
            $item = $this->findBySource($data);

            if ($item?->trashed()) {
                // Lo borró un admin: no se resucita ni se actualiza.
                Log::warning('Catálogo: el ítem de esta fuente está borrado; no se reimporta.', [
                    'item_id' => $item->id, 'provider' => $data->provider, 'external_id' => $data->externalId,
                ]);

                return $item;
            }

            [$item, $wikidataId] = $this->matchWikidata($data, $category, $item);

            // Solo los ítems nuevos nacen activos; al refrescar, el status no se toca (decisión del humano, F1-04).
            $item ??= new Item([
                'category_id' => $category->id,
                'creator_id' => null,
                'images' => [],
                'status' => Item::STATUS_ACTIVE,
            ]);
            $item->fill($this->itemAttributes($data, $item, $wikidataId));
            $item->save(); // slug al crear (Item::booted) y quality_score en saving

            $this->syncSources($item, $data);

            return $item;
        });
    }

    /**
     * El proveedor devolvió 404 (pipeline §5): el ítem se desactiva, no se borra (puede tener votos).
     */
    public function deactivate(string $provider, string $externalId): ?Item
    {
        $source = ItemSource::query()->where('provider', $provider)->where('external_id', $externalId)->first();
        $item = $source === null ? null : Item::query()->find($source->item_id);

        if ($item === null) {
            Log::info('Catálogo: la fuente devolvió 404 y no hay ítem que desactivar.', [
                'provider' => $provider, 'external_id' => $externalId,
            ]);

            return null;
        }

        if ($item->status !== Item::STATUS_INACTIVE) {
            $item->status = Item::STATUS_INACTIVE;
            $item->save();
        }

        Log::warning('Catálogo: la fuente ya no tiene el ítem (404); se desactiva.', [
            'item_id' => $item->id, 'provider' => $provider, 'external_id' => $externalId,
        ]);

        return $item;
    }

    /** Ítem (incluso borrado) enlazado a la fuente del DTO. Nunca se busca por nombre. */
    private function findBySource(CatalogItemData $data): ?Item
    {
        $source = ItemSource::query()
            ->where('provider', $data->provider)
            ->where('external_id', $data->externalId)
            ->first();

        return $source === null ? null : Item::withTrashed()->lockForUpdate()->find($source->item_id);
    }

    /**
     * Resuelve qué hacer con el QID de Wikidata del DTO.
     *
     * @return array{0: ?Item, 1: ?string} el ítem a actualizar (o null si hay que crearlo) y el QID a guardar
     */
    private function matchWikidata(CatalogItemData $data, Category $category, ?Item $item): array
    {
        $qid = $data->wikidataId;

        if ($qid === null) {
            return [$item, null];
        }

        // withTrashed: el índice único de wikidata_id incluye los ítems borrados.
        $holder = Item::withTrashed()
            ->where('wikidata_id', $qid)
            ->when($item !== null, fn ($q) => $q->whereKeyNot($item->id))
            ->lockForUpdate()
            ->first();

        if ($holder === null) {
            return [$item, $qid];
        }

        // Mismo ítem por QID dentro de la categoría (pipeline §4, paso 1.2), salvo que ya tenga otra ficha
        // primaria del mismo proveedor.
        if (
            $item === null
            && ! $holder->trashed()
            && $holder->category_id === $category->id
            && ! $holder->sources()
                ->where('is_primary', true)
                ->where('provider', $data->provider)
                ->where('external_id', '!=', $data->externalId)
                ->exists()
        ) {
            return [$holder, $qid];
        }

        Log::warning('Catálogo: el QID ya pertenece a otro ítem; se importa sin wikidata_id.', [
            'qid' => $qid,
            'provider' => $data->provider,
            'external_id' => $data->externalId,
            'other_item_id' => $holder->id,
            'other_category_id' => $holder->category_id,
        ]);

        return [$item, null];
    }

    /** @return array<string, mixed> */
    private function itemAttributes(CatalogItemData $data, Item $item, ?string $wikidataId): array
    {
        $attributes = [
            'name' => $data->name,
            'original_title' => $data->originalTitle,
            'released_at' => $data->releasedAt,
            'attributes' => $data->attributes,
            'popularity' => $data->popularity,
            'wikidata_id' => $wikidataId ?? $item->wikidata_id,
            'last_synced_at' => now(),
            // Sin 'status': un ítem desactivado a mano (o por un 404) sigue inactivo al refrescar.
        ];

        // Si el proveedor ya no trae descripción, se conserva una de otra fuente (Wikipedia, Wikidata, manual)
        // en lugar de borrarla por un fallo temporal; la del propio proveedor sí se borra (términos de TMDB).
        $keep = $data->description === null && $item->exists
            && filled($item->description) && $item->description_source !== $data->provider;

        if (! $keep) {
            $attributes += [
                'description' => $data->description,
                'description_source' => $data->descriptionSource,
                'description_url' => $data->descriptionUrl,
            ];
        }

        return $attributes;
    }

    /** Deja la fuente del proveedor como única primaria y mantiene la de Wikidata al día. */
    private function syncSources(Item $item, CatalogItemData $data): void
    {
        $now = now();

        ItemSource::query()->updateOrCreate(
            ['provider' => $data->provider, 'external_id' => $data->externalId],
            ['item_id' => $item->id, 'url' => $data->sourceUrl, 'is_primary' => true, 'last_synced_at' => $now],
        );

        // Una sola fuente primaria por ítem.
        $item->sources()
            ->where('is_primary', true)
            ->where(fn ($q) => $q->where('provider', '!=', $data->provider)->orWhere('external_id', '!=', $data->externalId))
            ->update(['is_primary' => false]);

        $qid = $item->wikidata_id;

        $stale = $item->sources()->where('provider', self::WIKIDATA_PROVIDER);
        if ($qid !== null) {
            $stale->where('external_id', '!=', $qid);
        }
        $stale->delete();

        if ($qid !== null) {
            ItemSource::query()->updateOrCreate(
                ['provider' => self::WIKIDATA_PROVIDER, 'external_id' => $qid],
                ['item_id' => $item->id, 'url' => self::WIKIDATA_URL.$qid, 'is_primary' => false, 'last_synced_at' => $now],
            );
        }
    }
}
