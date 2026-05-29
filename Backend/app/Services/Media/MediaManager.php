<?php

namespace App\Services\Media;

use App\Contracts\Media\ImageProcessorInterface;
use App\Contracts\Media\MediaStorageInterface;

class MediaManager
{
    public function __construct(
        protected ImageProcessorInterface $processor,
        protected MediaStorageInterface $storage
    ) {}

    public function processItemVariants(string $absoluteTempPath, string $targetDirectory, ?string $filename = null): array
    {
        $base = $filename ?: uniqid('', true);

        $thumb = $this->processAndStore($absoluteTempPath, "$targetDirectory/{$base}-thumb.webp", 400);
        $banner = $this->processAndStore($absoluteTempPath, "$targetDirectory/{$base}-banner.webp", 1280);

        return [
            'variants' => [
                'thumb' => $thumb['path'],
                'banner' => $banner['path'],
            ],
            // Usamos los metadatos del thumbnail original para guardar la proporción real
            'meta' => $thumb['meta'],
        ];
    }

    public function processAvatar(string $absoluteTempPath, string $targetDirectory, ?string $filename = null): array
    {
        $base = $filename ?: uniqid('', true);
        return $this->processAndStore($absoluteTempPath, "$targetDirectory/avatar-{$base}.webp", 256, true);
    }

    public function processCategory(string $absoluteTempPath, string $targetDirectory, ?string $filename = null): array
    {
        $base = $filename ?: uniqid('', true);
        return $this->processAndStore($absoluteTempPath, "$targetDirectory/cover-{$base}.webp", 800);
    }

    /**
     * Orquesta el ciclo de vida: procesa con Intervention, sube con Storage y limpia la basura.
     */
    private function processAndStore(string $sourcePath, string $targetPath, int $width, bool $cropToSquare = false): array
    {
        $processed = $this->processor->convertToWebp($sourcePath, 80, $width, null, $cropToSquare);
        $finalPath = $this->storage->storeFromPath('public', $targetPath, $processed['path'], 'public');

        @unlink($processed['path']);

        return [
            'path' => $finalPath,
            'meta' => $processed['meta']
        ];
    }
}
