<?php

namespace App\Contracts\Media;

interface ImageProcessorInterface
{
    /**
     * Interfaz que sirve de molde al servicio de procesamiento de imágenes.
     */
    public function convertToWebp(string $sourcePath, int $quality, int $width, int $height, bool $cropToSquare = false): array;
    public function extractMeta(string $sourcePath): array;
}
