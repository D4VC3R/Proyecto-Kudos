<?php

namespace App\Services\Media;

use App\Contracts\Media\ImageProcessorInterface;
use App\Contracts\Media\MediaStorageInterface;

/**
 * MediaManager es el servicio central que orquesta el procesamiento y almacenamiento de imágenes.
 * Se encarga de generar variantes específicas para ítems, avatares y categorías, utilizando
 * el ImageProcessor para transformar las imágenes y el MediaStorage para guardarlas.
 */
class MediaManager
{
    public function __construct(
        protected ImageProcessorInterface $processor,
        protected MediaStorageInterface $storage
    ) {}

    /**
     * Procesa una imagen de ítem para generar variantes de thumbnail y banner, y las almacena.
     * Devuelve las rutas de las variantes junto con los metadatos del thumbnail.
     *
     * @param string $absoluteTempPath Ruta absoluta del archivo temporal a procesar.
     * @param string $targetDirectory Directorio dentro del disco donde se almacenarán las variantes.
     * @param string|null $filename Nombre base opcional para las variantes (sin extensión).
     * @return array Contiene 'variants' => rutas de las variantes y 'meta' => metadatos del thumbnail.
     */
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

    /**
     * Procesa una imagen de avatar, recortándola a un cuadrado y redimensionándola a 256x256, luego la almacena.
     * Devuelve la ruta del avatar junto con sus metadatos.
     *
     * @param string $absoluteTempPath Ruta absoluta del archivo temporal a procesar.
     * @param string $targetDirectory Directorio dentro del disco donde se almacenará el avatar.
     * @param string|null $filename Nombre base opcional para el avatar (sin extensión).
     * @return array Contiene 'path' => ruta del avatar almacenado y 'meta' => metadatos del avatar.
     */
    public function processAvatar(string $absoluteTempPath, string $targetDirectory, ?string $filename = null): array
    {
        $base = $filename ?: uniqid('', true);
        return $this->processAndStore($absoluteTempPath, "$targetDirectory/avatar-{$base}.webp", 256, true);
    }

    /**
     * Procesa una imagen de categoría, redimensionándola a un ancho máximo de 800px, luego la almacena.
     * Devuelve la ruta de la imagen junto con sus metadatos.
     *
     * @param string $absoluteTempPath Ruta absoluta del archivo temporal a procesar.
     * @param string $targetDirectory Directorio dentro del disco donde se almacenará la imagen de categoría.
     * @param string|null $filename Nombre base opcional para la imagen de categoría (sin extensión).
     * @return array Contiene 'path' => ruta de la imagen almacenada y 'meta' => metadatos de la imagen.
     */
    public function processCategory(string $absoluteTempPath, string $targetDirectory, ?string $filename = null): array
    {
        $base = $filename ?: uniqid('', true);
        return $this->processAndStore($absoluteTempPath, "$targetDirectory/cover-{$base}.webp", 800);
    }

    /**
     * Función privada que centraliza el proceso de conversión a WebP y almacenamiento.
     * Permite configurar el ancho deseado y si se debe recortar a un cuadrado.
     *
     * @param string $sourcePath Ruta absoluta del archivo fuente a procesar.
     * @param string $targetPath Ruta dentro del disco donde se almacenará el archivo procesado.
     * @param int $width Ancho deseado para la imagen procesada.
     * @param bool $cropToSquare Si es true, recorta la imagen a un cuadrado basado en el ancho.
     * @return array Contiene 'path' => ruta del archivo almacenado y 'meta' => metadatos extraídos.
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
