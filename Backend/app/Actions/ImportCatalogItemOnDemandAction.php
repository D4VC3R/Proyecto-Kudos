<?php

namespace App\Actions;

use App\Catalog\CatalogImporter;
use App\Catalog\CatalogProviderRegistry;
use App\Catalog\Concerns\CallsProvidersOnDemand;
use App\Catalog\Contracts\CatalogProvider;
use App\Catalog\Data\OnDemandImportResult;
use App\Catalog\Enrichment\DescriptionResolver;
use App\Catalog\Exceptions\CatalogUnavailableException;
use App\Jobs\Catalog\DownloadItemImagesJob;
use App\Models\Item;
use App\Models\ItemSource;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Importa un título de la fuente externa a petición de un usuario (F1-07, pipeline-catalogo §7).
 *
 * Un candado por (proveedor, título) evita que dos peticiones simultáneas lo importen a la vez: la segunda
 * espera y, al obtener el candado, encuentra la fuente ya creada y responde "ya existe". Si la espera se
 * agota, se responde como fuente no disponible. No reutiliza ImportCatalogItemJob porque la respuesta es
 * síncrona (el usuario espera) y el proveedor se llama con el timeout corto de catalog.on_demand.
 * El ítem lo crea el sistema (creator_id = null); en el log queda quién lo pidió.
 */
class ImportCatalogItemOnDemandAction
{
    use CallsProvidersOnDemand;

    public function __construct(
        private readonly CatalogProviderRegistry $registry,
        private readonly DescriptionResolver $resolver,
        private readonly CatalogImporter $importer,
    ) {}

    /**
     * @throws CatalogUnavailableException si la fuente falla o hay otra importación del mismo título en curso
     */
    public function execute(User $user, string $categorySlug, string $externalId): OnDemandImportResult
    {
        $provider = $this->registry->forCategory($categorySlug);
        $lock = Cache::lock('catalog:on-demand-import:'.$provider->key().':'.$externalId, 60);

        try {
            $result = $lock->block(
                (int) config('catalog.on_demand.lock_wait_seconds', 10),
                fn () => $this->importLocked($provider, $externalId),
            );
        } catch (LockTimeoutException $e) {
            throw new CatalogUnavailableException('Otra importación del mismo título sigue en curso.', previous: $e);
        }

        // El sistema crea el ítem (creator_id = null); en el log queda quién lo pidió (pipeline §7).
        Log::info('Catálogo: importación bajo demanda.', [
            'user_id' => $user->id, 'category' => $categorySlug, 'external_id' => $externalId,
            'outcome' => $result->outcome, 'item_id' => $result->item?->id,
        ]);

        return $result;
    }

    private function importLocked(CatalogProvider $provider, string $externalId): OnDemandImportResult
    {
        $source = ItemSource::query()
            ->where('provider', $provider->key())
            ->where('external_id', $externalId)
            ->first();

        if ($source !== null) {
            return $this->resultFor(Item::withTrashed()->find($source->item_id), created: false);
        }

        $data = $this->callProvider(fn () => $provider->fetch($externalId));

        if ($data === null) {
            return new OnDemandImportResult(OnDemandImportResult::NOT_FOUND);
        }

        $data = $this->withShortHttpTimeout(fn () => $this->resolver->resolve($data));
        $item = $this->importer->import($data);

        if (! $item->trashed()) {
            // Se encola siempre, como en ImportCatalogItemJob: el job solo descarga lo que cambia o falta.
            DownloadItemImagesJob::dispatch($item->id, $data->images);
        }

        return $this->resultFor($item, $item->wasRecentlyCreated);
    }

    private function resultFor(?Item $item, bool $created): OnDemandImportResult
    {
        if ($item === null) {
            return new OnDemandImportResult(OnDemandImportResult::UNAVAILABLE);
        }

        if ($created) {
            return new OnDemandImportResult(OnDemandImportResult::CREATED, $item);
        }

        if ($item->trashed() || $item->status !== Item::STATUS_ACTIVE) {
            return new OnDemandImportResult(OnDemandImportResult::UNAVAILABLE, $item);
        }

        return new OnDemandImportResult(OnDemandImportResult::EXISTING, $item);
    }
}
