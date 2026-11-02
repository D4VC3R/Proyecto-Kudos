<?php

namespace App\Catalog\Clients;

use App\Catalog\Clients\Concerns\ThrottlesRequests;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Cliente de Wikimedia Commons (F4-02, fuentes-externas §4): licencia, autor y página de un archivo, con el límite
 * compartido de Wikimedia. Las fotos se descargan desde Special:FilePath (DownloadItemImagesJob).
 */
final class CommonsClient
{
    use ThrottlesRequests;

    public const API_URL = 'https://commons.wikimedia.org/w/api.php';

    public const FILE_PATH_URL = 'https://commons.wikimedia.org/wiki/Special:FilePath/';

    public const FILE_PAGE_URL = 'https://commons.wikimedia.org/wiki/File:';

    public const AUTHOR_MAX_LENGTH = 250;

    /**
     * Licencia (LicenseShortName), autor (Artist en texto plano) y página del archivo, o null si el archivo no existe.
     * license o author valen null si Commons no los da.
     *
     * @return array{license: ?string, author: ?string, source_url: string}|null
     *
     * @throws RequestException ante un 429/5xx persistente
     * @throws RuntimeException si la API devuelve un error
     */
    public function imageInfo(string $fileName): ?array
    {
        $fileName = self::normalize($fileName);

        if ($fileName === '') {
            return null;
        }

        $response = $this->request()->get(self::API_URL, [
            'action' => 'query',
            'prop' => 'imageinfo',
            'iiprop' => 'extmetadata|url',
            'iiextmetadatafilter' => 'LicenseShortName|Artist',
            'titles' => 'File:'.$fileName,
            'format' => 'json',
            'formatversion' => 2,
        ]);

        if ($response->notFound()) {
            return null;
        }

        $response->throw();

        if ($response->json('error') !== null) {
            throw new RuntimeException('Commons devolvió un error: '.$response->json('error.code'));
        }

        $page = $response->json('query.pages.0');

        if (! is_array($page) || ! empty($page['missing']) || ! empty($page['invalid'])) {
            return null;
        }

        $info = $page['imageinfo'][0] ?? null;

        if (! is_array($info)) {
            return null;
        }

        $author = self::plainText($info['extmetadata']['Artist']['value'] ?? null);
        $url = $info['descriptionurl'] ?? null;

        return [
            'license' => self::plainText($info['extmetadata']['LicenseShortName']['value'] ?? null),
            'author' => $author !== null ? Str::limit($author, self::AUTHOR_MAX_LENGTH) : null,
            'source_url' => is_string($url) && $url !== '' ? $url : self::filePageUrl($fileName),
        ];
    }

    /** URL de la foto a un ancho dado (Special:FilePath redirige a la miniatura de upload.wikimedia.org). */
    public static function filePathUrl(string $fileName, int $width): string
    {
        return self::FILE_PATH_URL.self::encode(self::normalize($fileName)).'?width='.$width;
    }

    public static function filePageUrl(string $fileName): string
    {
        return self::FILE_PAGE_URL.self::encode(self::normalize($fileName));
    }

    /** Nombre del archivo sin el prefijo File: y sin espacios sobrantes. */
    private static function normalize(string $fileName): string
    {
        return trim((string) preg_replace('/^(File|Archivo):/i', '', trim($fileName)));
    }

    private static function encode(string $fileName): string
    {
        return rawurlencode(str_replace(' ', '_', $fileName));
    }

    /** Texto plano: sin etiquetas, con las entidades HTML decodificadas y los espacios colapsados (Artist trae HTML). */
    private static function plainText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return $text !== '' ? $text : null;
    }

    private function request(): PendingRequest
    {
        return Http::catalog()->beforeSending(fn () => $this->throttle(
            'catalog:wikimedia',
            (int) config('catalog.providers.wikimedia.rate_per_minute', 150),
            60,
        ));
    }
}
