<?php

namespace App\Services\Media;

use App\Contracts\Media\ImageProcessorInterface;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use RuntimeException;
use Throwable;

class InterventionImageProcessor implements ImageProcessorInterface
{
    public function __construct(
        private readonly ImageManager $imageManager = new ImageManager(new Driver())
    ) {}

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
