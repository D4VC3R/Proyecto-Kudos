<?php

namespace Tests\Unit\Services\Media;

use App\Contracts\Media\HostResolverInterface;
use App\Services\Media\DnsHostResolver;
use App\Services\Media\RemoteImageDownloader;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Concerns\FakesRemoteImages;
use Tests\TestCase;

class RemoteImageDownloaderTest extends TestCase
{
    use FakesRemoteImages;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_descarga_la_imagen_si_el_host_resuelve_a_una_ip_publica(): void
    {
        $this->fakeHostResolver('8.8.8.8');
        Http::fake(['image.tmdb.org/*' => Http::response($this->fakePng(), 200, ['Content-Type' => 'image/png'])]);

        $path = app(RemoteImageDownloader::class)->downloadToTemp('https://image.tmdb.org/t/p/w780/a.jpg');

        Storage::disk('local')->assertExists($path);
    }

    public function test_rechaza_un_host_que_resuelve_a_una_ip_privada(): void
    {
        $this->fakeHostResolver('10.0.0.5');
        Http::fake();

        $this->assertThrows(
            fn () => app(RemoteImageDownloader::class)->downloadToTemp('https://image.tmdb.org/t/p/w780/a.jpg'),
            RuntimeException::class,
        );
        Http::assertNothingSent();
    }

    public function test_rechaza_un_host_que_resuelve_a_loopback(): void
    {
        $this->fakeHostResolver('127.0.0.1');
        Http::fake();

        $this->assertThrows(
            fn () => app(RemoteImageDownloader::class)->downloadToTemp('https://image.tmdb.org/t/p/w780/a.jpg'),
            RuntimeException::class,
        );
        Http::assertNothingSent();
    }

    public function test_rechaza_un_host_que_no_se_puede_resolver(): void
    {
        $this->fakeHostResolver(null);
        Http::fake();

        $this->assertThrows(
            fn () => app(RemoteImageDownloader::class)->downloadToTemp('https://image.tmdb.org/t/p/w780/a.jpg'),
            RuntimeException::class,
        );
        Http::assertNothingSent();
    }

    public function test_rechaza_un_esquema_no_permitido(): void
    {
        $this->fakeHostResolver('8.8.8.8');
        Http::fake();

        $this->assertThrows(
            fn () => app(RemoteImageDownloader::class)->downloadToTemp('file:///etc/passwd'),
            RuntimeException::class,
        );
        Http::assertNothingSent();
    }

    public function test_por_defecto_resuelve_con_dns(): void
    {
        $resolver = app(HostResolverInterface::class);

        $this->assertInstanceOf(DnsHostResolver::class, $resolver);
        // Una IP literal no hace consulta DNS.
        $this->assertSame('127.0.0.1', $resolver->resolve('127.0.0.1'));
    }

    public function test_envia_el_user_agent_del_catalogo(): void
    {
        config(['catalog.user_agent' => 'KudosTest/1.0 (+https://kudos.test)']);
        $this->fakeHostResolver('8.8.8.8');
        Http::fake(['upload.wikimedia.org/*' => Http::response($this->fakePng(), 200, ['Content-Type' => 'image/png'])]);

        app(RemoteImageDownloader::class)->downloadToTemp('https://upload.wikimedia.org/wikipedia/commons/a/ab/Madrid.png');

        $requests = Http::recorded();
        $this->assertCount(2, $requests); // HEAD y GET
        foreach ($requests as [$request]) {
            $this->assertTrue($request->hasHeader('User-Agent', 'KudosTest/1.0 (+https://kudos.test)'));
        }
    }

    public function test_acepta_cabeceras_en_minusculas_como_las_de_http2(): void
    {
        // upload.wikimedia.org (fotos de Commons, F4-02) responde por HTTP/2 con las cabeceras en minúsculas.
        $this->fakeHostResolver('8.8.8.8');
        Http::fake(['upload.wikimedia.org/*' => Http::response($this->fakePng(), 200, ['content-type' => 'image/png', 'content-length' => '1024'])]);

        $path = app(RemoteImageDownloader::class)->downloadToTemp('https://upload.wikimedia.org/wikipedia/commons/a/ab/Madrid.png');

        Storage::disk('local')->assertExists($path);
    }
}
