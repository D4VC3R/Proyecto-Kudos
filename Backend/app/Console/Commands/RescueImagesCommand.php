<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class RescueImagesCommand extends Command
{
	protected $signature = 'app:rescue-images';
	protected $description = 'Descarga y extrae las imágenes del seeder directamente en el volumen';

	public function handle()
	{
		$this->info('Iniciando descarga desde Google Drive...');

		$targetDir = storage_path('app/public');
		$fileName = 'rescate-imagenes.tar.gz';
		$fileId = '1QsX33KsyVllpJ38FBdzlNHTckav-mO4t';

		// Usamos wget con el flag --no-check-certificate y gestionamos el token de confirmación de forma limpia
		$command = "cd {$targetDir} && " .
			"wget --no-check-certificate 'https://docs.google.com/uc?export=download&id={$fileId}' -O {$fileName} && " .
			"tar -xzvf {$fileName} && " .
			"rm {$fileName}";

		$result = Process::run($command);

		if ($result->successful()) {
			$this->info('¡Imágenes descargadas y extraídas con éxito en el volumen!');
			$this->line($result->output());
		} else {
			$this->error('Hubo un error en la descarga o extracción:');
			$this->error($result->errorOutput());
			$this->line($result->output());
		}
	}
}