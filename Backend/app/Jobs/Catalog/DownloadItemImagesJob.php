<?php

namespace App\Jobs\Catalog;

use App\Contracts\Media\MediaStorageInterface;
use App\Models\Item;
use App\Services\Media\MediaManager;
use App\Services\Media\RemoteImageDownloader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Descarga las imágenes de un ítem del catálogo y las guarda como variantes WebP en
 * `items/{categoría}/{id}/{md5(url)}-{thumb|banner}.webp` (pipeline-catalogo §4). Cola 'catalog'.
 *
 * Es idempotente: una imagen que el ítem ya tiene (misma URL, variantes presentes en disco) no se vuelve a
 * descargar. Si una imagen falla se sigue con las demás, y si fallan todas se conservan las actuales.
 */
class DownloadItemImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    private const PUBLIC_DISK = 'public';

    private const TEMP_DISK = 'local';

    private const TEMP_DIR = 'temp_uploads';

    /**
     * @param  list<array{url: string, role: string, license: ?string, author: ?string, source_url: ?string}>  $images
     */
    public function __construct(public string $itemId, public array $images)
    {
        $this->onQueue('catalog');
    }

    public function handle(RemoteImageDownloader $downloader, MediaManager $media, MediaStorageInterface $storage): void
    {
        $item = Item::query()->find($this->itemId);

        if ($item === null) {
            return; // no existe o está borrado
        }

        $directory = 'items/'.($item->category()->value('slug') ?? 'general').'/'.$item->id;
        $current = array_values(array_filter((array) $item->images, 'is_array'));
        $public = Storage::disk(self::PUBLIC_DISK);

        $new = [];
        $failures = 0;

        foreach ($this->images as $image) {
            $url = $image['url'] ?? null;

            if (! is_string($url) || $url === '') {
                continue;
            }

            $hash = md5($url);
            $variants = [
                'thumb' => "{$directory}/{$hash}-thumb.webp",
                'banner' => "{$directory}/{$hash}-banner.webp",
            ];

            $existing = $this->findCurrent($current, $variants);

            if ($existing !== null && $public->exists($variants['thumb']) && $public->exists($variants['banner'])) {
                $processed = ['variants' => $variants, 'meta' => $existing['meta'] ?? []];
            } else {
                $tempPath = null;

                try {
                    $tempPath = $downloader->downloadToTemp($url, self::TEMP_DISK, self::TEMP_DIR);
                    $processed = $media->processItemVariants(Storage::disk(self::TEMP_DISK)->path($tempPath), $directory, $hash);
                } catch (Throwable $e) {
                    $failures++;
                    Log::warning('Catálogo: no se pudo descargar una imagen; se sigue con las demás.', [
                        'item_id' => $item->id, 'url' => $url, 'error' => $e->getMessage(),
                    ]);

                    continue;
                } finally {
                    if ($tempPath !== null) {
                        $storage->delete(self::TEMP_DISK, $tempPath);
                    }
                }
            }

            $new[] = [
                'disk' => self::PUBLIC_DISK,
                'variants' => $processed['variants'],
                'meta' => $processed['meta'] ?? [],
                'alt' => $item->name,
                'order' => count($new),
                'role' => $image['role'] ?? null,
                'license' => $image['license'] ?? null,
                'author' => $image['author'] ?? null,
                'source_url' => $image['source_url'] ?? null,
            ];
        }

        if ($new === [] && $failures > 0 && $current !== []) {
            Log::warning('Catálogo: fallaron todas las imágenes; se conservan las actuales.', [
                'item_id' => $item->id, 'failures' => $failures,
            ]);

            return;
        }

        // Comparación no estricta a propósito: jsonb no conserva el orden de las claves, y así no se guarda
        // (ni se recalcula la calidad) cuando no ha cambiado nada.
        if ($new != $current) {
            $item->images = $new;
            $item->save(); // el hook saving recalcula quality_score
        }

        $this->deleteStale($storage, $directory, $current, $new);
    }

    /**
     * Entrada actual con las mismas variantes esperadas (misma URL, por el hash del nombre).
     *
     * @param  list<array<string, mixed>>  $current
     * @param  array{thumb: string, banner: string}  $variants
     * @return array<string, mixed>|null
     */
    private function findCurrent(array $current, array $variants): ?array
    {
        foreach ($current as $entry) {
            if (($entry['variants']['thumb'] ?? null) === $variants['thumb'] && ($entry['variants']['banner'] ?? null) === $variants['banner']) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Borra las variantes de este ítem que ya no están en la lista nueva. Solo las de `items/{categoría}/{id}/`:
     * nunca las de semillas o propuestas.
     *
     * @param  list<array<string, mixed>>  $current
     * @param  list<array<string, mixed>>  $new
     */
    private function deleteStale(MediaStorageInterface $storage, string $directory, array $current, array $new): void
    {
        $kept = [];
        foreach ($new as $entry) {
            foreach ((array) ($entry['variants'] ?? []) as $path) {
                $kept[] = $path;
            }
        }

        foreach ($current as $entry) {
            foreach ((array) ($entry['variants'] ?? []) as $path) {
                if (is_string($path) && str_starts_with($path, "{$directory}/") && ! in_array($path, $kept, true)) {
                    $storage->delete(self::PUBLIC_DISK, $path);
                }
            }
        }
    }
}
