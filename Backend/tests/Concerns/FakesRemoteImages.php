<?php

namespace Tests\Concerns;

use App\Contracts\Media\HostResolverInterface;
use Illuminate\Http\UploadedFile;

/**
 * Descargas de imágenes sin red (R4): resolución de host falseada y PNG reales generados con GD.
 */
trait FakesRemoteImages
{
    /** Sustituye la resolución DNS de RemoteImageDownloader. Con null, el host "no resuelve" (devuelve el host). */
    protected function fakeHostResolver(?string $ip = '8.8.8.8'): void
    {
        $this->app->instance(HostResolverInterface::class, new class($ip) implements HostResolverInterface
        {
            public function __construct(private readonly ?string $ip) {}

            public function resolve(string $host): string
            {
                return $this->ip ?? $host;
            }
        });
    }

    /** Contenido de un PNG válido para Http::fake(). */
    protected function fakePng(int $width = 40, int $height = 60): string
    {
        // Se guarda en una variable: el archivo temporal se borra cuando el UploadedFile se destruye.
        $file = UploadedFile::fake()->image('imagen.png', $width, $height);

        return (string) file_get_contents($file->getRealPath());
    }
}
