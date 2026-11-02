<?php

namespace Tests\Unit\Catalog\Clients;

use App\Catalog\Clients\CommonsClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\Concerns\LoadsFixtures;
use Tests\TestCase;

class CommonsClientTest extends TestCase
{
    use LoadsFixtures;

    private const COMMONS = 'commons.wikimedia.org/w/api.php*';

    protected function setUp(): void
    {
        parent::setUp();

        config(['catalog.user_agent' => 'KudosTest/1.0 (+https://kudos.test)']);
        Sleep::fake(); // anula las esperas de los reintentos y del RateLimiter
    }

    public function test_image_info_devuelve_licencia_autor_en_texto_plano_y_pagina_del_archivo(): void
    {
        Http::fake([self::COMMONS => Http::response($this->fixture('catalog/commons/imageinfo-con-licencia.json'))]);

        $info = app(CommonsClient::class)->imageInfo('Madrid - Palacio Real.jpg');

        $this->assertSame('Ejemplo & otros', $info['author']);
        $this->assertSame('CC BY-SA 4.0', $info['license']);
        $this->assertSame('https://commons.wikimedia.org/wiki/File:Madrid_-_Palacio_Real.jpg', $info['source_url']);
        Http::assertSent(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->hasHeader('User-Agent', 'KudosTest/1.0 (+https://kudos.test)')
                && $query['action'] === 'query'
                && $query['prop'] === 'imageinfo'
                && $query['iiprop'] === 'extmetadata|url'
                && $query['titles'] === 'File:Madrid - Palacio Real.jpg';
        });
    }

    public function test_image_info_quita_el_prefijo_file(): void
    {
        Http::fake([self::COMMONS => Http::response($this->fixture('catalog/commons/imageinfo-con-licencia.json'))]);

        app(CommonsClient::class)->imageInfo('File:Madrid - Palacio Real.jpg');

        Http::assertSent(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $query['titles'] === 'File:Madrid - Palacio Real.jpg';
        });
    }

    public function test_image_info_sin_artist_devuelve_author_null(): void
    {
        Http::fake([self::COMMONS => Http::response($this->fixture('catalog/commons/imageinfo-sin-autor.json'))]);

        $info = app(CommonsClient::class)->imageInfo('Madrid - Palacio Real.jpg');

        $this->assertNull($info['author']);
        $this->assertSame('CC BY-SA 4.0', $info['license']);
    }

    public function test_image_info_sin_licencia_devuelve_license_null(): void
    {
        Http::fake([self::COMMONS => Http::response($this->fixture('catalog/commons/imageinfo-sin-licencia.json'))]);

        $info = app(CommonsClient::class)->imageInfo('Madrid - Palacio Real.jpg');

        $this->assertNull($info['license']);
        $this->assertSame('Ejemplo & otros', $info['author']);
    }

    public function test_image_info_devuelve_null_si_el_archivo_no_existe(): void
    {
        Http::fake([self::COMMONS => Http::response($this->fixture('catalog/commons/imageinfo-no-existe.json'))]);

        $this->assertNull(app(CommonsClient::class)->imageInfo('No existe.jpg'));
    }

    public function test_image_info_reintenta_ante_un_429(): void
    {
        Http::fake([self::COMMONS => Http::sequence()
            ->push([], 429)
            ->push($this->fixture('catalog/commons/imageinfo-con-licencia.json')),
        ]);

        $info = app(CommonsClient::class)->imageInfo('Madrid - Palacio Real.jpg');

        $this->assertSame('CC BY-SA 4.0', $info['license']);
        Http::assertSentCount(2);
    }

    public function test_file_path_url_codifica_el_nombre_y_fija_el_ancho(): void
    {
        $this->assertSame(
            'https://commons.wikimedia.org/wiki/Special:FilePath/Madrid_-_Palacio_Real.jpg?width=1280',
            CommonsClient::filePathUrl('Madrid - Palacio Real.jpg', 1280),
        );
    }
}
