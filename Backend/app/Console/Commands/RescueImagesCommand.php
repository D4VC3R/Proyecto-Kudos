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
		$this->info('Iniciando descarga desde enlace directo...');

		$targetDir = storage_path('app/public');
		$tarGzPath = $targetDir . '/rescate-imagenes.tar.gz';

		$url = 'https://www.dropbox.com/scl/fi/es9wh2u1ik3vldysz4j8e/rescate-imagenes.tar.gz?rlkey=4l845hqxfginbucrzvw6bio1q&st=sx3mxu2w&dl=1';

		try {
			$this->info('Descargando los 188MB de datos (esto puede tardar unos segundos)...');

			// Descarga directa al disco usando un "sink" para no saturar la memoria RAM del contenedor
			$response = Http::withOptions(['sink' => $tarGzPath])->get($url);

			if (!$response->successful() || !file_exists($tarGzPath) || filesize($tarGzPath) < 100000) {
				throw new Exception('El archivo descargado es inválido o corrupto. Revisa que el enlace sea directo.');
			}

			$this->info('Descomprimiendo el archivo .tar.gz...');

			// Extraemos .gz a .tar
			$p = new PharData($tarGzPath);
			$tarPath = $p->decompress();

			// Extraemos .tar al directorio final
			$tar = new PharData($tarPath->getPathname());
			$tar->extractTo($targetDir, null, true);

			// Limpieza de los archivos comprimidos temporales
			unlink($tarGzPath);
			unlink($tarPath->getPathname());

			$this->info('¡Imágenes descargadas y extraídas con éxito en el volumen!');

		} catch (Exception $e) {
			$this->error('Hubo un error en el proceso:');
			$this->error($e->getMessage());

			// Evitamos dejar basura en el volumen si algo falla
			if (file_exists($tarGzPath)) {
				unlink($tarGzPath);
			}
		}
	}
}