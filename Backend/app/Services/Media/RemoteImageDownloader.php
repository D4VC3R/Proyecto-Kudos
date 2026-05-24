<?php

namespace App\Services\Media;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class RemoteImageDownloader
{
    /**
     * Descarga una imagen remota a disco temporal con protecciones SSRF.
     */
    public function downloadToTemp(
        string $url,
        int $maxBytes = 5_242_880,
        string $tempDisk = 'local',
        string $tempDir = 'temp_uploads',
    ): string {
        $this->assertSafeUrl($url);

        $head = Http::timeout(10)->head($url);
        if ($head->successful()) {
            $this->assertImageHeaders($head->headers(), $maxBytes);
        }

        Storage::disk($tempDisk)->makeDirectory($tempDir);
        $tempPath = $tempDir . '/' . Str::uuid()->toString() . '.tmp';
        $absolutePath = Storage::disk($tempDisk)->path($tempPath);

        $response = Http::timeout(30)
            ->withOptions(['stream' => true])
            ->get($url);

        if (!$response->successful()) {
            throw new RuntimeException('No se pudo descargar la imagen remota.');
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        if ($contentType === '' || !str_starts_with($contentType, 'image/')) {
            throw new RuntimeException('El contenido remoto no es una imagen valida.');
        }

        $body = $response->getBody();
        $handle = fopen($absolutePath, 'wb');
        if ($handle === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal.');
        }

        $bytes = 0;
        while (!$body->eof()) {
            $chunk = $body->read(8192);
            if ($chunk === '') {
                continue;
            }

            $bytes += strlen($chunk);
            if ($bytes > $maxBytes) {
                fclose($handle);
                @unlink($absolutePath);
                throw new RuntimeException('La imagen remota supera el máximo permitido.');
            }

            fwrite($handle, $chunk);
        }

        fclose($handle);

        if (!is_file($absolutePath)) {
            throw new RuntimeException('No se genero el archivo temporal descargado.');
        }

        return $tempPath;
    }

    public function isRemoteUrl(string $value): bool
    {
        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
    }

    /**
     * @param array<string, array<int, string>> $headers
     */
    private function assertImageHeaders(array $headers, int $maxBytes): void
    {
        $contentType = strtolower((string) ($headers['Content-Type'][0] ?? ''));
        if ($contentType === '' || !str_starts_with($contentType, 'image/')) {
            throw new RuntimeException('El recurso remoto no es una imagen.');
        }

        $contentLength = (int) ($headers['Content-Length'][0] ?? 0);
        if ($contentLength > 0 && $contentLength > $maxBytes) {
            throw new RuntimeException('El recurso remoto supera el máximo permitido.');
        }
    }

    private function assertSafeUrl(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('URL invalida.');
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = (string) ($parts['host'] ?? '');

        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new RuntimeException('URL invalida.');
        }

        $ip = gethostbyname($host);
        if ($ip === $host && !filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new RuntimeException('No se pudo resolver el host remoto.');
        }

        if ($this->isPrivateIp($ip)) {
            throw new RuntimeException('El host remoto no es accesible.');
        }
    }

    private function isPrivateIp(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        $long = ip2long($ip);
        if ($long === false) {
            return true;
        }

        $privateRanges = [
            ['10.0.0.0', '10.255.255.255'],
            ['127.0.0.0', '127.255.255.255'],
            ['169.254.0.0', '169.254.255.255'],
            ['172.16.0.0', '172.31.255.255'],
            ['192.168.0.0', '192.168.255.255'],
        ];

        foreach ($privateRanges as [$start, $end]) {
            if ($long >= ip2long($start) && $long <= ip2long($end)) {
                return true;
            }
        }

        return false;
    }
}

