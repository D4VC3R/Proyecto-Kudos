<?php

namespace App\Services\Media;

use App\Contracts\Media\ImageProcessorInterface;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use RuntimeException;
use Throwable;

/**
 * Servicio que implementa ImageProcessorInterface usando Intervention Image.
 * Se encarga de convertir imágenes a WebP, extraer metadatos y manejar errores específicos.
 */
class InterventionImageProcessor implements ImageProcessorInterface
{
    public function __construct(
        private readonly ImageManager $imageManager = new ImageManager(new Driver())
    ) {}

    /**
     * Convierte una imagen a formato WebP con opciones de calidad, tamaño y recorte.
     *
     * @param string $sourcePath Ruta absoluta del archivo fuente.
     * @param int $quality Calidad de compresión (0-100).
     * @param int|null $width Ancho deseado (opcional).
     * @param int|null $height Alto deseado (opcional).
     * @param bool $cropToSquare Si es true, recorta la imagen a un cuadrado basado en el ancho.
     * @return array Contiene 'path' => ruta del archivo WebP generado y 'meta' => metadatos extraídos.
     * @throws RuntimeException Si el archivo fuente no existe o si falla la conversión.
     */
    public function convertToWebp(string $sourcePath, int $quality = 80, ?int $width = null, ?int $height = null, bool $cropToSquare = false): array
    {
        if (!is_file($sourcePath)) {
            throw new RuntimeException('No se encontró el archivo fuente para conversión a WebP.');
        }

        $directory = dirname($sourcePath);
        $baseName = pathinfo($sourcePath, PATHINFO_FILENAME);
        $targetPath = $directory . DIRECTORY_SEPARATOR . $baseName . '-' . uniqid('', true) . '.webp';

        try {
            $image = $this->imageManager->read($sourcePath);

            $meta = $this->extractMetaFromInstance($image);

            if ($cropToSquare && $width !== null) {
                $image->cover($width, $width);
            } elseif ($width !== null && $height !== null) {
                $image->pad($width, $height, 'transparent');
            } elseif ($width !== null) {
                $image->scaleDown(width: $width);
            }

            $image->toWebp($quality)->save($targetPath);
        } catch (Throwable $exception) {
            throw new RuntimeException('Falló la conversión de imagen a WebP.', 0, $exception);
        }

        if (!is_file($targetPath)) {
            throw new RuntimeException('No se pudo generar el archivo WebP.');
        }

        return [
            'path' => $targetPath,
            'meta' => $meta,
        ];
    }

    /**
     * Extrae metadatos de una imagen dada su ruta.
     *
     * @param string $sourcePath Ruta absoluta del archivo fuente.
     * @return array Contiene 'width', 'height', 'orientation', 'aspect_ratio' y 'color' (dominante en hexadecimal).
     * @throws RuntimeException Si el archivo no existe o si falla la extracción de metadatos.
     */
    public function extractMeta(string $sourcePath): array
    {
        if (!is_file($sourcePath)) {
            throw new RuntimeException('No se encontró el archivo para extraer metadatos.');
        }

        try {
            $image = $this->imageManager->read($sourcePath);
            return $this->extractMetaFromInstance($image);
        } catch (Throwable $exception) {
            throw new RuntimeException('Falló la extracción de metadatos.', 0, $exception);
        }
    }

    /**
     * Extrae metadatos de una instancia de imagen de Intervention.
     *
     * @param ImageInterface $image La instancia de imagen de la cual extraer los metadatos.
     * @return array Contiene 'width', 'height', 'orientation', 'aspect_ratio' y 'color' (dominante en hexadecimal).
     */
    private function extractMetaFromInstance(ImageInterface $image): array
    {
        $width = $image->width();
        $height = $image->height();

        $orientation = match (true) {
            $width > $height => 'landscape',
            $width < $height => 'portrait',
            default => 'square',
        };

        $aspectRatio = $height > 0 ? round($width / $height, 4) : 1;

        $colorClone = clone $image;
        $dominantColor = $colorClone->resize(1, 1)->pickColor(0, 0)->toHex();
        unset($colorClone);

        return [
            'width' => $width,
            'height' => $height,
            'orientation' => $orientation,
            'aspect_ratio' => $aspectRatio,
            'color' => $dominantColor,
        ];
    }
}
