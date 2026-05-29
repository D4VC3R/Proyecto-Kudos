<?php

namespace App\Contracts\Media;

use Illuminate\Http\UploadedFile;

/**
 * Interfaz que define las operaciones de almacenamiento de archivos multimedia.
 */
interface MediaStorageInterface
{
    /**
     * Almacena un archivo subido y devuelve la ruta final.
     * @param UploadedFile $file El archivo subido a almacenar.
     * @param string $disk El disco donde se almacenará el archivo.
     * @param string $directory El directorio dentro del disco donde se almacenará el archivo.
     * @param string|null $visibility La visibilidad del archivo (pública o privada).
     */
    public function storeUploadedFile(
        UploadedFile $file,
        string $disk,
        string $directory,
        ?string $visibility = null,
    ): string;

    /**
     * Almacena desde una ruta local en el disco indicado y devuelve la ruta final.
     * @param string $disk El disco donde se almacenará el archivo.
     * @param string $path La ruta dentro del disco donde se almacenará el archivo.
     * @param string $sourcePath La ruta local del archivo a almacenar.
     * @param string|null $visibility La visibilidad del archivo (pública o privada).
     */
    public function storeFromPath(
        string $disk,
        string $path,
        string $sourcePath,
        ?string $visibility = null,
    ): string;

    /**
     * Mueve un archivo entre discos/rutas y devuelve la nueva ruta.
     * @param string $fromDisk El disco de origen del archivo.
     * @param string $fromPath La ruta de origen del archivo dentro del disco.
     * @param string $toDisk El disco de destino del archivo.
     * @param string $toPath La ruta de destino del archivo dentro del disco.
     * @param string|null $visibility La visibilidad del archivo (pública o privada)
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
     * @param string $disk El disco del archivo a eliminar.
     * @param string $path La ruta del archivo a eliminar dentro del disco.
     */
    public function delete(string $disk, string $path): void;
}

