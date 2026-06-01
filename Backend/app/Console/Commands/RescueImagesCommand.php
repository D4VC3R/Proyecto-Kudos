<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Exception;

class RescueImagesCommand extends Command
{
	protected $signature = 'app:rescue-images';
	protected $description = 'Descarga de forma nativa y extrae mediante sistema operativo';

	public function handle()
	{
		$this->info('Iniciando descarga desde enlace directo...');

		$targetDir = storage_path('app/public');
		$fileName = 'rescate-imagenes.tar.gz';
		$tarGzPath = $targetDir . '/' . $fileName;

		// Mantén aquí tu enlace de Dropbox (terminado en ?dl=1) o Transfer.sh
		$url = 'TU_ENLACE_DIRECTO_AQUI';

		try {
			$this->info('Descargando los 188MB de datos (esto puede tardar unos segundos)...');

			// 1. Descarga nativa y robusta con PHP, gestionando redirecciones automáticamente
			$response = Http::withOptions(['sink' => $tarGzPath])->get($url);

			// Verificamos que sea un archivo real y pesado, no una página HTML de error
			if (!$response->successful() || !file_exists($tarGzPath) || filesize($tarGzPath) < 100000) {
				throw new Exception('El archivo descargado es inválido o corrupto. Revisa el enlace directo.');
			}

			$this->info('Descomprimiendo el archivo utilizando el sistema nativo de Linux...');

			// 2. Extracción delegada al sistema operativo, saltándonos las limitaciones de PharData
			$command = "cd {$targetDir} && tar -xzvf {$fileName}";
			$result = Process::run($command);

			if (!$result->successful()) {
				throw new Exception("Error del sistema operativo al extraer: " . $result->errorOutput());
			}

			// 3. Limpieza del archivo comprimido original
			unlink($tarGzPath);

			$this->info('¡Imágenes descargadas y extraídas con éxito en el volumen!');
			// Opcional: mostrar la lista de archivos extraídos
			// $this->line($result->output());

		} catch (Exception $e) {
			$this->error('Hubo un error en el proceso:');
			$this->error($e->getMessage());

			// Limpieza de seguridad en caso de fallo crítico
			if (file_exists($tarGzPath)) {
				unlink($tarGzPath);
			}
		}
	}
}