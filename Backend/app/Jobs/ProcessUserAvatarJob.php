<?php

namespace App\Jobs;

use App\Contracts\Media\MediaStorageInterface;
use App\Models\User;
use App\Services\Media\MediaManager;
use App\Services\Media\RemoteImageDownloader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Job encargado de procesar el avatar de un usuario.
 * Este job trata de descargar la imagen del avatar (si es una URL remota), procesarla (redimensionar, generar variantes, etc.)
 * y almacenarla en el sistema de archivos, actualizando el perfil del usuario con la ruta del avatar procesado.
 */
class ProcessUserAvatarJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MAX_BYTES = 5_242_880;
    private const TEMP_DISK = 'local';
    private const TEMP_DIR = 'temp_uploads';

    public function __construct(
        public User $user,
        public string $avatarInput,
    ) {}

    /**
     * Maneja la lógica principal del job:
     * - Refresca la instancia del usuario para asegurarse de tener los datos más recientes.
     * - Verifica si el input del avatar no está vacío, si es una URL remota, intenta descargarla a un directorio temporal.
     * - Procesa la imagen del avatar utilizando el MediaManager y almacena el resultado en el sistema de archivos.
     * - Actualiza el perfil del usuario con la ruta del avatar procesado, creando un perfil si no existe (no deberia de pasar).
     */
   public function handle(
   		RemoteImageDownloader $downloader,
   		MediaManager $mediaManager,
   		MediaStorageInterface $storage,
   	): void {
   		$user = $this->user->fresh(['profile']);

   		if (!$user) {
   			return;
   		}

   		$input = trim($this->avatarInput);
   		if ($input === '') {
   			return;
   		}

   		$tempPath = $downloader->isRemoteUrl($input)
   			? $downloader->downloadToTemp($input, self::MAX_BYTES, self::TEMP_DISK, self::TEMP_DIR)
   			: $input;

   		$absoluteTempPath = Storage::disk(self::TEMP_DISK)->path($tempPath);
   		$avatarData = $mediaManager->processAvatar($absoluteTempPath, 'avatars/' . $user->id);
   		$storedPath = $avatarData['path'];

   		$storage->delete(self::TEMP_DISK, $tempPath);

   		if ($user->profile) {
   			$user->profile->update(['avatar' => $storedPath]);
   			return;
   		}

   		$user->profile()->create(['avatar' => $storedPath]);
   	}
}
