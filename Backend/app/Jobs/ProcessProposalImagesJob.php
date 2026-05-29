<?php

namespace App\Jobs;

use App\Contracts\Media\MediaStorageInterface;
use App\Models\Proposal;
use App\Services\Media\MediaManager;
use App\Services\Media\RemoteImageDownloader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Job encargado de procesar las imágenes asociadas a una propuesta.
 * Este job se encarga de descargar imágenes remotas, procesarlas (redimensionar, generar variantes, etc.)
 * y almacenarlas en el sistema de archivos, actualizando la propuesta con las rutas de las imágenes procesadas.
 */
class ProcessProposalImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MAX_BYTES = 5_242_880;
    private const TEMP_DIR = 'temp_uploads';
    private const TEMP_DISK = 'local';
    private const PUBLIC_DISK = 'public';

    public function __construct(
        public Proposal $proposal,
        public array $rawItems,
    ) {}

    /**
     * Maneja la lógica principal del job:
     * - Refresca la instancia de la propuesta para asegurarse de tener los datos más recientes.
     * - Itera sobre cada imagen cruda proporcionada, procesándola y generando las variantes necesarias.
     * - Si el procesamiento es exitoso, actualiza la propuesta con las rutas de las imágenes procesadas.
     * - Si ocurre algún error durante el procesamiento de una imagen, se registra una advertencia en los logs.
     */
    public function handle(
        MediaStorageInterface $storage,
        RemoteImageDownloader $downloader,
        MediaManager $mediaManager,
    ): void {
        $proposal = $this->proposal->fresh();
        if (!$proposal) return;

        $categorySlug = $proposal->category()->value('slug') ?? 'general';
        $images = [];

        foreach ($this->rawItems as $rawItem) {
            try {
                // processItem devuelve un array: ['variants' => [...], 'meta' => [...]]
                $processedData = $this->processItem($rawItem, $proposal, $categorySlug, $downloader, $mediaManager, $storage);

                if (!empty($processedData) && isset($processedData['variants'])) {
                    $images[] = [
                        'variants' => $processedData['variants'],
                        'meta' => $processedData['meta'] ?? [],
                        'disk' => self::PUBLIC_DISK,
                        'alt' => null,
                        'order' => count($images),
                    ];
                }
            } catch (Throwable $exception) {
                Log::warning('No se pudo procesar imagen de propuesta.', [
                    'proposal_id' => $proposal->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $proposal->update(['images' => $images]);
    }

    /**
     * Procesa una imagen individual:
     * - Verifica si la entrada es una URL remota o una ruta local.
     * - Si es una URL remota, la descarga a un directorio temporal.
     * - Procesa la imagen utilizando el MediaManager para generar las variantes necesarias.
     * - Elimina el archivo temporal después del procesamiento.
     * - Devuelve un array con las rutas de las variantes generadas y cualquier metadato relevante.
     */
    private function processItem(
        mixed $rawItem, Proposal $proposal, string $categorySlug,
        RemoteImageDownloader $downloader, MediaManager $mediaManager, MediaStorageInterface $storage
    ): array {
        if (!is_string($rawItem) || trim($rawItem) === '') return [];

        $rawItem = trim($rawItem);
        $tempPath = $downloader->isRemoteUrl($rawItem)
            ? $downloader->downloadToTemp($rawItem, self::TEMP_DISK, self::TEMP_DIR)
            : $rawItem;

        $absoluteTempPath = Storage::disk(self::TEMP_DISK)->path($tempPath);

        $targetDir = 'proposals/' . $categorySlug . '/' . $proposal->id;
        $processedData = $mediaManager->processItemVariants($absoluteTempPath, $targetDir);

        $storage->delete(self::TEMP_DISK, $tempPath);

        return $processedData;
    }
}
