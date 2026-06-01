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

		$command = "cd {$targetDir} && " .
			"curl -Lb /tmp/cookies.txt \"https://docs.google.com/uc?export=download&confirm=$(curl -sL -b /tmp/cookies.txt 'https://docs.google.com/uc?export=download&id={$fileId}' | grep -o 'confirm=[^&]*' | sed 's/confirm=//')&id={$fileId}\" -o {$fileName} && " .
			"rm -rf /tmp/cookies.txt && " .
			"tar -xzvf {$fileName} && " .
			"rm {$fileName}";

		$result = Process::run($command);

		if ($result->successful()) {
			$this->info('¡Imágenes descargadas y extraídas con éxito en el volumen!');
			$this->line($result->output());
		} else {
			$this->error('Hubo un error en la descarga:');
			$this->error($result->errorOutput());
		}
	}
}