<?php

namespace App\Services\Media;

use App\Contracts\Media\ImageProcessorInterface;
use Intervention\Image\ImageManager;
use RuntimeException;

class InterventionImageProcessor implements ImageProcessorInterface
{
	public function __construct(
		private readonly ImageManager $imageManager = new ImageManager(new \Intervention\Image\Drivers\Gd\Driver())
	) {}

	public function convertToWebp(string $sourcePath, int $quality = 80, ?int $width = null, ?int $height = null, bool $cropToSquare = false): string
	{
		if (!is_file($sourcePath)) {
			throw new RuntimeException('No se encontro el archivo fuente para conversion a WebP.');
		}

		$directory = dirname($sourcePath);
		$baseName = pathinfo($sourcePath, PATHINFO_FILENAME);
		$targetPath = $directory . DIRECTORY_SEPARATOR . $baseName . '-' . uniqid('', true) . '.webp';

		try {
			$image = $this->imageManager->read($sourcePath);

			if ($cropToSquare && $width !== null) {
				// Para avatares: cover() sigue siendo correcto aquí
				$image->cover($width, $width);
			} elseif ($width !== null && $height !== null) {
				// Para banners: encaja toda la imagen dentro de los límites.
				// Lo que sobra se rellena (he puesto 'transparent', pero puedes usar '000000' para negro puro)
				$image->pad($width, $height, 'transparent');
			} elseif ($width !== null) {
				// Para miniaturas y categorías
				$image->scaleDown(width: $width);
			}

			$image->toWebp($quality)->save($targetPath);
		} catch (\Throwable $exception) {
			throw new RuntimeException('Fallo la conversion de imagen a WebP.', 0, $exception);
		}

		if (!is_file($targetPath)) {
			throw new RuntimeException('No se pudo generar el archivo WebP.');
		}

		return $targetPath;
	}
}