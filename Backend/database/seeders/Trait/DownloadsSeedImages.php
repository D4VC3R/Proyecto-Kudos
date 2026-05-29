<?php

namespace Database\Seeders\Trait;

use App\Contracts\Media\ImageProcessorInterface;
use App\Services\Media\MediaManager;
use App\Services\Media\RemoteImageDownloader;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Trait que proporciona funcionalidades para descargar y almacenar imágenes remotas durante el proceso de seeding.
 * Permite normalizar las imágenes definidas en los snapshots, descargarlas a un almacenamiento temporal,
 * procesarlas y almacenarlas en el disco público, evitando descargas repetidas mediante la verificación de existencia previa.
 */
trait DownloadsSeedImages
{
    private const TEMP_DISK = 'local';
    private const TEMP_DIR = 'temp_uploads';
    private const PUBLIC_DISK = 'public';

    /**
     * Normaliza el array de imágenes definido en los snapshots, descargando las imágenes remotas y almacenándolas.
     * Para cada imagen, verifica si es una URL remota y, de ser así, intenta descargarla y procesarla antes de almacenarla.
     * Si la imagen ya existe en el almacenamiento público, reutiliza la ruta existente para evitar descargas innecesarias.
     *
     * @param array $images El array de imágenes a normalizar, cada una con 'path', 'alt' y 'order'.
     * @param string $categorySlug El slug de la categoría para organizar las imágenes descargadas.
     * @param string $bucket El bucket o carpeta dentro del disco público donde se almacenarán las imágenes.
     * @return array El array de imágenes normalizadas con rutas locales para las imágenes remotas.
     */
    protected function normalizeSeedImages(array $images, string $categorySlug, string $bucket): array
    {
        $normalized = [];
        $downloader = app(RemoteImageDownloader::class);

        foreach ($images as $image) {
            if (!is_array($image)) continue;

            $path = $image['path'] ?? null;
            if (!is_string($path) || $path === '') continue;

            $alt = $image['alt'] ?? null;
            $order = $image['order'] ?? 0;

            if ($downloader->isRemoteUrl($path)) {
                $storedData = $this->downloadAndStoreImage($path, $categorySlug, $bucket);
                if ($storedData !== null) {
                    $item = ['disk' => self::PUBLIC_DISK, 'alt' => $alt, 'order' => $order];

                    if (is_array($storedData) && isset($storedData['variants'])) {
                        $item['variants'] = $storedData['variants'];
                        $item['meta'] = $storedData['meta'] ?? [];
                    } elseif (is_array($storedData) && isset($storedData['path'])) {
                        $item['path'] = $storedData['path'];
                        $item['meta'] = $storedData['meta'] ?? [];
                    } else {
                        $item['path'] = $storedData;
                    }

                    $normalized[] = $item;
                }
                continue;
            }

            $normalized[] = ['path' => $path, 'disk' => $image['disk'] ?? self::PUBLIC_DISK, 'alt' => $alt, 'order' => $order];
        }

        return $normalized;
    }

    /**
     * Descarga una imagen desde una URL remota, la procesa y la almacena en el disco público.
     * Verifica si la imagen ya existe antes de descargarla para evitar descargas repetidas.
     * En caso de error durante la descarga o el procesamiento, registra una advertencia y retorna null.
     *
     * @param string $url La URL de la imagen remota a descargar.
     * @param string $categorySlug El slug de la categoría para organizar las imágenes descargadas.
     * @param string $bucket El bucket o carpeta dentro del disco público donde se almacenarán las imágenes.
     * @return string|array|null Retorna la ruta de la imagen almacenada, un array con variantes y metadatos, o null en caso de error.
     */
    protected function downloadAndStoreImage(string $url, string $categorySlug, string $bucket): string|array|null
    {
        $hash = md5($url);
        $baseDir = $bucket . '/' . $categorySlug . '/seed';
        $processor = app(ImageProcessorInterface::class);

        if ($bucket === 'categories') {
            $expectedPath = "$baseDir/cover-{$hash}.webp";
            if (Storage::disk(self::PUBLIC_DISK)->exists($expectedPath)) {
                $meta = $processor->extractMeta(Storage::disk(self::PUBLIC_DISK)->path($expectedPath));
                return ['path' => $expectedPath, 'meta' => $meta];
            }
        } else {
            $expectedThumb = "$baseDir/{$hash}-thumb.webp";
            $expectedBanner = "$baseDir/{$hash}-banner.webp";
            if (Storage::disk(self::PUBLIC_DISK)->exists($expectedThumb) && Storage::disk(self::PUBLIC_DISK)->exists($expectedBanner)) {
                $meta = $processor->extractMeta(Storage::disk(self::PUBLIC_DISK)->path($expectedBanner));
                return ['variants' => ['thumb' => $expectedThumb, 'banner' => $expectedBanner], 'meta' => $meta];
            }
        }

        try {
            $tempPath = $this->downloadToTemp($url);
            $absoluteTempPath = Storage::disk(self::TEMP_DISK)->path($tempPath);

            $mediaManager = app(MediaManager::class);

            if ($bucket === 'categories') {
                $storedData = $mediaManager->processCategory($absoluteTempPath, $baseDir, $hash);
            } else {
                $storedData = $mediaManager->processItemVariants($absoluteTempPath, $baseDir, $hash);
            }

            Storage::disk(self::TEMP_DISK)->delete($tempPath);

            return $storedData;
        } catch (Throwable $e) {
            Log::warning("Error en seeder: No se pudo descargar o procesar la imagen {$url}. Error: {$e->getMessage()}");
            if (app()->runningInConsole()) dump("Aviso: Falló la descarga de {$url}");
            return null;
        }
    }

    /** Descarga una imagen desde una URL remota y la guarda en un almacenamiento temporal.
     * Retorna la ruta relativa del archivo temporal donde se guardó la imagen.
     *
     * @param string $url La URL de la imagen remota a descargar.
     * @return string La ruta relativa del archivo temporal donde se guardó la imagen.
     * @throws Throwable Si ocurre un error durante la descarga de la imagen.
     */
    private function downloadToTemp(string $url): string
    {
        return app(RemoteImageDownloader::class)->downloadToTemp($url, self::TEMP_DISK, self::TEMP_DIR);
    }
}
