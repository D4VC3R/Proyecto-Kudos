<?php

namespace App\Services\Media;

use App\Contracts\Media\MediaStorageInterface;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Implementación de MediaStorageInterface utilizando el sistema de archivos de Laravel.
 */
class LaravelMediaStorage implements MediaStorageInterface
{
    /**
     * Almacena un archivo subido y devuelve la ruta final.
     *
     * @param UploadedFile $file El archivo subido a almacenar.
     * @param string $disk El disco donde se almacenará el archivo.
     * @param string $directory El directorio dentro del disco donde se almacenará el archivo.
     * @param string|null $visibility La visibilidad del archivo (pública o privada).
     * @return string La ruta del archivo almacenado dentro del disco.
     * @throws RuntimeException Si no se pudo almacenar el archivo subido.
     */
    public function storeUploadedFile(UploadedFile $file, string $disk, string $directory, ?string $visibility = null): string {
        $options = $visibility ? ['visibility' => $visibility] : [];
        $stored = Storage::disk($disk)->putFile($directory, $file, $options);

        if (!is_string($stored) || $stored === '') {
            throw new RuntimeException('No se pudo almacenar el archivo subido.');
        }

        return $stored;
    }

    /**
     * Almacena desde una ruta local en el disco indicado y devuelve la ruta final.
     *
     * @param string $disk El disco donde se almacenará el archivo.
     * @param string $path La ruta dentro del disco donde se almacenará el archivo.
     * @param string $sourcePath La ruta local del archivo a almacenar.
     * @param string|null $visibility La visibilidad del archivo (pública o privada).
     * @return string La ruta del archivo almacenado dentro del disco.
     * @throws RuntimeException Si no se pudo almacenar el archivo desde la ruta local.
     */
    public function storeFromPath(string $disk, string $path, string $sourcePath, ?string $visibility = null): string {
        if (!is_file($sourcePath)) {
            throw new RuntimeException('No se encontró el archivo fuente para almacenamiento.');
        }

        $directory = trim((string) dirname($path), '.');
        $filename = basename($path);
        $options = $visibility ? ['visibility' => $visibility] : [];

        $stored = Storage::disk($disk)->putFileAs($directory, new File($sourcePath), $filename, $options);

        if (!is_string($stored) || $stored === '') {
            throw new RuntimeException('No se pudo almacenar el archivo desde path.');
        }

        return $stored;
    }

    /**
     * Mueve un archivo entre discos/rutas y devuelve la nueva ruta.
     *
     * @param string $fromDisk El disco de origen del archivo.
     * @param string $fromPath La ruta de origen del archivo dentro del disco.
     * @param string $toDisk El disco de destino del archivo.
     * @param string $toPath La ruta de destino del archivo dentro del disco.
     * @param string|null $visibility La visibilidad del archivo (pública o privada).
     * @return string La ruta del archivo movido dentro del disco de destino.
     * @throws RuntimeException Si no se pudo mover el archivo.
     */
    public function move(string $fromDisk, string $fromPath, string $toDisk, string $toPath, ?string $visibility = null): string {
        if ($fromDisk === $toDisk && Storage::disk($fromDisk)->move($fromPath, $toPath)) {
            if ($visibility) {
                Storage::disk($toDisk)->setVisibility($toPath, $visibility);
            }
            return $toPath;
        }

        if (!Storage::disk($fromDisk)->exists($fromPath)) {
            throw new RuntimeException('No se encontró el archivo fuente para mover.');
        }

        $stream = Storage::disk($fromDisk)->readStream($fromPath);
        if ($stream === false) {
            throw new RuntimeException('No se pudo leer el archivo fuente para mover.');
        }

        $options = $visibility ? ['visibility' => $visibility] : [];
        $written = Storage::disk($toDisk)->writeStream($toPath, $stream, $options);

        if (is_resource($stream)) {
            fclose($stream);
        }

        if (!$written) {
            throw new RuntimeException('No se pudo escribir el archivo destino al mover.');
        }

        Storage::disk($fromDisk)->delete($fromPath);

        return $toPath;
    }

    /**
     * Elimina un archivo del disco indicado.
     *
     * @param string $disk El disco del archivo a eliminar.
     * @param string $path La ruta del archivo a eliminar dentro del disco.
     */
    public function delete(string $disk, string $path): void
    {
        if ($path === '') {
            return;
        }

        Storage::disk($disk)->delete($path);
    }
}

