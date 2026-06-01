<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use PharData;
use Exception;

class RescueImagesCommand extends Command
{
	protected $signature = 'app:rescue-images';
	protected $description = 'Descarga y extrae las imágenes del seeder de forma nativa en PHP';

	public function handle()
	{
		$this->info('Iniciando descarga nativa desde Google Drive...');

		$targetDir = storage_path('app/public');
		$fileName = 'rescate-imagenes.tar'; // Lo descargamos directamente como TAR si es posible, o mantenemos el formato.
		$tarGzPath = $targetDir . '/rescate-imagenes.tar.gz';
		$fileId = '1QsX33KsyVllpJ38FBdzlNHTckav-mO4t';

		try {
			// 1. Petición HTTP inicial para obtener el token de confirmación de Google Drive
			$url = "https://docs.google.com/uc?export=download&id={$fileId}";
			$response = Http::get($url);

			$cookieHeader = $response->header('Set-Cookie');

			// Buscamos si Google nos pide confirmación de tamaño/virus
			if (preg_html_match('/confirm=([^&]*)/', $response->body(), $matches)) {
				$confirmToken = $matches[1];
				$url .= "&confirm={$confirmToken}";
			}

			$this->info('Descargando los 188MB de datos...');

			// 2. Descarga del archivo binario grande y almacenamiento en el disco local
			$downloadResponse = Http::withHeaders([
				'Cookie' => $cookieHeader
			])->withOptions([
				'sink' => $tarGzPath // Guarda el flujo directamente en el archivo para no saturar la memoria RAM
			])->get($url);

			if (!$downloadResponse->successful() || !file_exists($tarGzPath) || filesize($tarGzPath) < 100000) {
				throw new Exception('El archivo descargado es demasiado pequeño o corrupto. Verifica los permisos del enlace de Drive.');
			}

			$this->info('Extrayendo imágenes en el volumen...');

			// 3. Descompresión nativa (Equivalente a tar -xzvf)
			// Primero descomprimimos el gzip (.gz) para obtener el .tar
			$p = new PharData($tarGzPath);
			$tarPath = $p->decompress();

			// Segundo, extraemos el contenido del .tar en la carpeta destino
			$tar = new PharData($tarPath->getPathname());
			$tar->extractTo($targetDir, null, true);

			// 4. Limpieza de archivos temporales
			unlink($tarGzPath);
			unlink($tarPath->getPathname());

			$this->info('¡Imágenes descargadas y extraídas con éxito de forma nativa!');

		} catch (Exception $e) {
			$this->error('Hubo un error en el proceso nativo:');
			$this->error($e->getMessage());

			// Limpieza en caso de fallo para no dejar archivos residuales corruptos
			if (file_exists($tarGzPath)) unlink($tarGzPath);
		}
	}
}

/**
 * Función helper rápida para emular el comportamiento de buscar el token de confirmación.
 */
function preg_html_match($pattern, $subject, &$matches) {
	return preg_match($pattern, $subject, $matches);
}