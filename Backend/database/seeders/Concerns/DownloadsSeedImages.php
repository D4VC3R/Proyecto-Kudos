<?php

namespace Database\Seeders\Concerns;

use App\Services\Media\MediaManager;
use App\Services\Media\RemoteImageDownloader;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

trait DownloadsSeedImages
{
	private const SEED_MAX_BYTES = 5_242_880;
	private const TEMP_DISK = 'local';
	private const TEMP_DIR = 'temp_uploads';
	private const PUBLIC_DISK = 'public';

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

					if (is_array($storedData)) {
						$item['variants'] = $storedData;
					} else {
						$item['path'] = $storedData; // Por si cambias de idea y las categorias van en el JSON
					}
					$normalized[] = $item;
				}
				continue;
			}

			// Fallback manual
			$normalized[] = ['path' => $path, 'disk' => $image['disk'] ?? self::PUBLIC_DISK, 'alt' => $alt, 'order' => $order];
		}

		return $normalized;
	}

	/**
	 * @return string|array<string, string>|null
	 */
	protected function downloadAndStoreImage(string $url, string $categorySlug, string $bucket): string|array|null
	{
		$hash = md5($url);
		$baseDir = $bucket . '/' . $categorySlug . '/seed';

		// Comprobar Caché Determinista
		if ($bucket === 'categories') {
			$expectedPath = "$baseDir/cover-{$hash}.webp";
			if (Storage::disk(self::PUBLIC_DISK)->exists($expectedPath)) return $expectedPath;
		} else {
			$expectedThumb = "$baseDir/{$hash}-thumb.webp";
			$expectedBanner = "$baseDir/{$hash}-banner.webp";
			if (Storage::disk(self::PUBLIC_DISK)->exists($expectedThumb) && Storage::disk(self::PUBLIC_DISK)->exists($expectedBanner)) {
				return ['thumb' => $expectedThumb, 'banner' => $expectedBanner];
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
		} catch (\Throwable $e) {
			Log::warning("Error en seeder: No se pudo descargar o procesar la imagen {$url}. Error: {$e->getMessage()}");
			if (app()->runningInConsole()) dump("Aviso: Falló la descarga de {$url}");
			return null;
		}
	}

	private function downloadToTemp(string $url): string
	{
		return app(RemoteImageDownloader::class)->downloadToTemp($url, self::SEED_MAX_BYTES, self::TEMP_DISK, self::TEMP_DIR);
	}
}