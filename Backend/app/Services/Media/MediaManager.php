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

	/**
	 * Procesa imagenes de Items/Propuestas (Genera Thumb y Banner)
	 * @return array<string, mixed> ['variants' => ['thumb' => '...', 'banner' => '...'], 'meta' => [...]]
	 */
	public function processItemVariants(string $absoluteTempPath, string $targetDirectory, ?string $filename = null): array
	{
		$base = $filename ?: uniqid('', true);

		$thumbData = $this->processor->convertToWebp($absoluteTempPath, 80, 400);
		$thumbTemp = $thumbData['path'];
		$meta = $thumbData['meta']; // Capturamos los metadatos de la imagen original

		$thumbPath = $this->storage->storeFromPath('public', "$targetDirectory/{$base}-thumb.webp", $thumbTemp, 'public');

		$bannerData = $this->processor->convertToWebp($absoluteTempPath, 80, 1280);
		$bannerTemp = $bannerData['path'];
		$bannerPath = $this->storage->storeFromPath('public', "$targetDirectory/{$base}-banner.webp", $bannerTemp, 'public');

		@unlink($thumbTemp);
		@unlink($bannerTemp);

		return [
			'variants' => [
				'thumb' => $thumbPath,
				'banner' => $bannerPath,
			],
			'meta' => $meta,
		];
	}

	/**
	 * Procesa imagenes de Avatares (Un solo tamaño, cuadrado)
	 * @return array<string, mixed> ['path' => '...', 'meta' => [...]]
	 */
	public function processAvatar(string $absoluteTempPath, string $targetDirectory, ?string $filename = null): array
	{
		$base = $filename ?: uniqid('', true);

		$avatarData = $this->processor->convertToWebp($absoluteTempPath, 80, 256, null, true);
		$avatarTemp = $avatarData['path'];

		$avatarPath = $this->storage->storeFromPath('public', "$targetDirectory/avatar-{$base}.webp", $avatarTemp, 'public');

		@unlink($avatarTemp);

		return [
			'path' => $avatarPath,
			'meta' => $avatarData['meta'],
		];
	}

	/**
	 * Procesa Portadas de Categorías (Un solo tamaño, escalado)
	 * @return array<string, mixed> ['path' => '...', 'meta' => [...]]
	 */
	public function processCategory(string $absoluteTempPath, string $targetDirectory, ?string $filename = null): array
	{
		$base = $filename ?: uniqid('', true);

		$categoryData = $this->processor->convertToWebp($absoluteTempPath, 80, 800);
		$categoryTemp = $categoryData['path'];

		$categoryPath = $this->storage->storeFromPath('public', "$targetDirectory/cover-{$base}.webp", $categoryTemp, 'public');

		@unlink($categoryTemp);

		return [
			'path' => $categoryPath,
			'meta' => $categoryData['meta'],
		];
	}
}