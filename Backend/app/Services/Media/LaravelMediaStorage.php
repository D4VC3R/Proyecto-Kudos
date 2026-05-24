<?php

namespace App\Services\Media;

use App\Contracts\Media\MediaStorageInterface;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class LaravelMediaStorage implements MediaStorageInterface
{
    public function storeUploadedFile(
        UploadedFile $file,
        string $disk,
        string $directory,
        ?string $visibility = null,
    ): string {
        $options = $visibility ? ['visibility' => $visibility] : [];
        $stored = Storage::disk($disk)->putFile($directory, $file, $options);

        if (!is_string($stored) || $stored === '') {
            throw new RuntimeException('No se pudo almacenar el archivo subido.');
        }

        return $stored;
    }

    public function storeFromPath(
        string $disk,
        string $path,
        string $sourcePath,
        ?string $visibility = null,
    ): string {
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

    public function move(
        string $fromDisk,
        string $fromPath,
        string $toDisk,
        string $toPath,
        ?string $visibility = null,
    ): string {
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

    public function delete(string $disk, string $path): void
    {
        if ($path === '') {
            return;
        }

        Storage::disk($disk)->delete($path);
    }
}

