<?php

namespace App\Services\Media;

use App\Contracts\Media\ImageProcessorInterface;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
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
			throw new RuntimeException('No se encontro el archivo fuente para conversion a WebP.');
		}

		$directory = dirname($sourcePath);
		$baseName = pathinfo($sourcePath, PATHINFO_FILENAME);
		$targetPath = $directory . DIRECTORY_SEPARATOR . $baseName . '-' . uniqid('', true) . '.webp';

		try {
			$image = $this->imageManager->read($sourcePath);

			$originalWidth = $image->width();
			$originalHeight = $image->height();

			if ($originalWidth > $originalHeight) {
				$orientation = 'landscape';
			} elseif ($originalWidth < $originalHeight) {
				$orientation = 'portrait';
			} else {
				$orientation = 'square';
			}

			$aspectRatio = $originalHeight > 0 ? round($originalWidth / $originalHeight, 4) : 1;


			$colorClone = clone $image;
			$dominantColor = $colorClone->resize(1, 1)->pickColor(0, 0)->toHex();
			unset($colorClone);

			if ($cropToSquare && $width !== null) {
				$image->cover($width, $width);
			} elseif ($width !== null && $height !== null) {
				$image->pad($width, $height, 'transparent');
			} elseif ($width !== null) {
				$image->scaleDown(width: $width);
			}

			$image->toWebp($quality)->save($targetPath);
		} catch (Throwable $exception) {
			throw new RuntimeException('Fallo la conversion de imagen a WebP.', 0, $exception);
		}

		if (!is_file($targetPath)) {
			throw new RuntimeException('No se pudo generar el archivo WebP.');
		}

		return [
			'path' => $targetPath,
			'meta' => [
				'width' => $originalWidth,
				'height' => $originalHeight,
				'orientation' => $orientation,
				'aspect_ratio' => $aspectRatio,
				'color' => $dominantColor,
			]
		];
	}
}