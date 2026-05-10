<?php

namespace App\Contracts\Media;

use Illuminate\Http\UploadedFile;

interface MediaStorageInterface
{
    /**
     * Almacena un archivo subido y devuelve la ruta final.
     */
    public function storeUploadedFile(
        UploadedFile $file,
        string $disk,
        string $directory,
        ?string $visibility = null,
    ): string;

    /**
     * Almacena desde una ruta local en el disco indicado y devuelve la ruta final.
     */
    public function storeFromPath(
        string $disk,
        string $path,
        string $sourcePath,
        ?string $visibility = null,
    ): string;

    /**
     * Mueve un archivo entre discos/rutas y devuelve la nueva ruta.
     */
    public function move(
        string $fromDisk,
        string $fromPath,
        string $toDisk,
        string $toPath,
        ?string $visibility = null,
    ): string;

    /**
     * Elimina un archivo del disco indicado.
     */
    public function delete(string $disk, string $path): void;
}

