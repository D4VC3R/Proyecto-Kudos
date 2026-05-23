<?php

namespace App\Contracts\Media;

interface ImageProcessorInterface
{
    /**
     * Convierte una imagen a WebP y devuelve la ruta del archivo generado.
     */
    public function convertToWebp(string $sourcePath, int $quality, int $width, int $height, bool $cropToSquare = false): array;
}
