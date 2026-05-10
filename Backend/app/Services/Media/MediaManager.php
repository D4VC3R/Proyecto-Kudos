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
	 * @return array<string, string> Rutas almacenadas ['thumb' => '...', 'banner' => '...']
	 */
	public function processItemVariants(string $absoluteTempPath, string $targetDirectory): array
	{
		// 1. Variante Thumb (Máx 400px ancho, alto automático)
		$thumbTemp = $this->processor->convertToWebp($absoluteTempPath, 80, 400);
		$thumbPath = $this->storage->storeFromPath('public', "$targetDirectory/thumb-" . uniqid() . ".webp", $thumbTemp, 'public');

		// 2. Variante Banner (Exacto 700x220)
		$bannerTemp = $this->processor->convertToWebp($absoluteTempPath, 80, 1280);
		$bannerPath = $this->storage->storeFromPath('public', "$targetDirectory/banner-" . uniqid() . ".webp", $bannerTemp, 'public');

		@unlink($thumbTemp);
		@unlink($bannerTemp);

		return [
			'thumb' => $thumbPath,
			'banner' => $bannerPath,
		];
	}

	/**
	 * Procesa imagenes de Avatares (Un solo tamaño, cuadrado)
	 */
	public function processAvatar(string $absoluteTempPath, string $targetDirectory): string
	{
		$avatarTemp = $this->processor->convertToWebp($absoluteTempPath, 80, 256, null, true);
		$avatarPath = $this->storage->storeFromPath('public', "$targetDirectory/avatar-" . uniqid() . ".webp", $avatarTemp, 'public');

		@unlink($avatarTemp);
		return $avatarPath;
	}

	/**
	 * Procesa Portadas de Categorías (Un solo tamaño, escalado)
	 */
	public function processCategory(string $absoluteTempPath, string $targetDirectory): string
	{
		$categoryTemp = $this->processor->convertToWebp($absoluteTempPath, 80, 800);
		$categoryPath = $this->storage->storeFromPath('public', "$targetDirectory/cover-" . uniqid() . ".webp", $categoryTemp, 'public');

		@unlink($categoryTemp);
		return $categoryPath;
	}
}