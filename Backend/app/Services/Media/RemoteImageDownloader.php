<?php

namespace App\Services\Media;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Servicio encargado de descargar imágenes remotas a un almacenamiento temporal, con validaciones de seguridad y formato.
 */
class RemoteImageDownloader
{
    /**
     * Descarga una imagen desde una URL remota a un almacenamiento temporal, validando su formato y tamaño.
     *
     * @param string $url La URL de la imagen remota a descargar.
     * @param string $tempDisk El disco de almacenamiento temporal donde se guardará la imagen descargada.
     * @param string $tempDir El directorio dentro del disco temporal donde se guardará la imagen descargada.
     * @return string La ruta relativa dentro del disco temporal donde se guardó la imagen descargada.
     * @throws RuntimeException Si la URL es inválida, el recurso no es una imagen permitida, o si ocurre un error durante la descarga o validación.
     */
    public function downloadToTemp(string $url, string $tempDisk = 'local', string $tempDir = 'temp_uploads'): string
    {
        $this->assertSafeUrl($url);
        $maxBytes = config('media.max_upload_size');

        $head = Http::timeout(10)->head($url);
        if ($head->successful()) {
            $this->assertImageHeaders($head->headers(), $maxBytes);
        }

        Storage::disk($tempDisk)->makeDirectory($tempDir);
        $tempPath = $tempDir . '/' . Str::uuid()->toString() . '.tmp';
        $absolutePath = Storage::disk($tempDisk)->path($tempPath);

        $response = Http::timeout(30)->withOptions(['stream' => true])->get($url);

        if (!$response->successful()) {
            throw new RuntimeException('No se pudo descargar la imagen.');
        }

        $this->assertMimeType($response->header('Content-Type'));

        $body = $response->getBody();
        $handle = fopen($absolutePath, 'wb');

        $bytes = 0;
        while (!$body->eof()) {
            $chunk = $body->read(8192);
            $bytes += strlen($chunk);

            if ($bytes > $maxBytes) {
                fclose($handle);
                @unlink($absolutePath);
                throw new RuntimeException('La imagen supera el máximo permitido.');
            }
            fwrite($handle, $chunk);
        }
        fclose($handle);

        return $tempPath;
    }

    /**
     * Verifica si un valor es una URL remota válida.
     *
     * @param string $value El valor a verificar.
     * @return bool Retorna true si el valor es una URL remota válida, false en caso contrario.
     */
    public function isRemoteUrl(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Valida los encabezados de una respuesta HTTP para asegurarse de que corresponden a una imagen permitida y no exceden el tamaño máximo.
     *
     * @param array $headers Los encabezados de la respuesta HTTP a validar.
     * @param int $maxBytes El tamaño máximo permitido en bytes para la imagen.
     * @throws RuntimeException Si el recurso no es una imagen permitida o si supera el tamaño máximo permitido.
     */
    private function assertImageHeaders(array $headers, int $maxBytes): void
    {
        $this->assertMimeType($headers['Content-Type'][0] ?? '');

        $contentLength = (int) ($headers['Content-Length'][0] ?? 0);
        if ($contentLength > 0 && $contentLength > $maxBytes) {
            throw new RuntimeException('El recurso remoto supera el máximo permitido.');
        }
    }

    /**
     * Valida que el tipo MIME de un recurso corresponda a uno de los formatos de imagen permitidos.
     *
     * @param string $contentType El valor del encabezado Content-Type a validar.
     * @throws RuntimeException Si el tipo MIME no es uno de los formatos de imagen permitidos.
     */
    private function assertMimeType(string $contentType): void
    {
        $cleanType = strtolower(explode(';', $contentType)[0]);
        if (!in_array($cleanType, config('media.allowed_mimes'), true)) {
            throw new RuntimeException('El recurso remoto no es un formato de imagen permitido (jpeg, png, webp).');
        }
    }

    /**
     * Valida que una URL sea segura para su uso, verificando su esquema, host y dirección IP.
     *
     * @param string $url La URL a validar.
     * @throws RuntimeException Si la URL es inválida, tiene un esquema no permitido, o si el host remoto no es accesible o pertenece a una red privada.
     */
    private function assertSafeUrl(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = strtolower(parse_url($url, PHP_URL_SCHEME));

        if (!in_array($scheme, ['http', 'https'], true) || !$host) {
            throw new RuntimeException('URL inválida o esquema no permitido.');
        }

        $ip = gethostbyname($host);

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new RuntimeException('El host remoto no es accesible o es una red privada.');
        }
    }
}
