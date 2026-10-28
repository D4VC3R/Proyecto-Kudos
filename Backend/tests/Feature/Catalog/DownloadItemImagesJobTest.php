<?php

namespace Tests\Feature\Catalog;

use App\Jobs\Catalog\DownloadItemImagesJob;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\FakesRemoteImages;
use Tests\TestCase;

class DownloadItemImagesJobTest extends TestCase
{
    use CreatesItems, FakesRemoteImages, RefreshDatabase;

    private const POSTER = 'https://image.tmdb.org/t/p/w780/poster.jpg';

    private const BANNER = 'https://image.tmdb.org/t/p/w1280/fondo.jpg';

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
        $this->fakeHostResolver();
        $this->category = $this->createCategory('peliculas', 'Películas');
    }

    /** @return array{url: string, role: string, license: string, author: ?string, source_url: string} */
    private function image(string $url, string $role): array
    {
        return [
            'url' => $url,
            'role' => $role,
            'license' => '© titular de derechos',
            'author' => null,
            'source_url' => 'https://www.themoviedb.org/movie/603',
        ];
    }

    /** @return list<array<string, mixed>> */
    private function both(): array
    {
        return [$this->image(self::POSTER, 'poster'), $this->image(self::BANNER, 'banner')];
    }

    /** Falsea image.tmdb.org con un PNG nuevo por petición (el cuerpo en streaming de una respuesta reutilizada se agotaría). */
    private function fakeImages(array $failing = []): void
    {
        $png = $this->fakePng();
        $rules = [];

        foreach ($failing as $pattern) {
            $rules[$pattern] = fn () => Http::response('', 404);
        }

        $rules['image.tmdb.org/*'] = fn () => Http::response($png, 200, ['Content-Type' => 'image/png']);

        Http::fake($rules);
    }

    private function imageRequests(): int
    {
        return Http::recorded(fn (Request $request) => str_starts_with($request->url(), 'https://image.tmdb.org/'))->count();
    }

    private function newItem(): Item
    {
        return $this->createItem($this->category, ['images' => []]);
    }

    public function test_descarga_las_imagenes_y_guarda_licencia_autor_y_fuente(): void
    {
        $this->fakeImages();
        $item = $this->newItem();
        $this->assertSame(40, $item->fresh()->quality_score);

        DownloadItemImagesJob::dispatchSync($item->id, $this->both());

        $item = $item->fresh();
        $this->assertCount(2, $item->images);

        [$poster, $banner] = $item->images;
        $this->assertSame('poster', $poster['role']);
        $this->assertSame('banner', $banner['role']);
        $this->assertSame('© titular de derechos', $poster['license']);
        $this->assertNull($poster['author']);
        $this->assertSame('https://www.themoviedb.org/movie/603', $poster['source_url']);
        $this->assertSame('public', $poster['disk']);
        $this->assertSame(0, $poster['order']);
        $this->assertSame(1, $banner['order']);

        $hash = md5(self::POSTER);
        $this->assertSame("items/peliculas/{$item->id}/{$hash}-thumb.webp", $poster['variants']['thumb']);
        foreach ($item->images as $image) {
            Storage::disk('public')->assertExists($image['variants']['thumb']);
            Storage::disk('public')->assertExists($image['variants']['banner']);
        }
        $this->assertSame(80, $item->quality_score);
    }

    public function test_si_falla_una_imagen_sigue_con_las_demas(): void
    {
        Log::spy();
        $this->fakeImages(['image.tmdb.org/t/p/w1280/*']);
        $item = $this->newItem();

        DownloadItemImagesJob::dispatchSync($item->id, $this->both());

        $item = $item->fresh();
        $this->assertCount(1, $item->images);
        $this->assertSame('poster', $item->images[0]['role']);
        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'no se pudo descargar una imagen'))
            ->once();
    }

    public function test_no_vuelve_a_descargar_las_imagenes_que_ya_tiene(): void
    {
        $this->fakeImages();
        $item = $this->newItem();

        DownloadItemImagesJob::dispatchSync($item->id, $this->both());
        DownloadItemImagesJob::dispatchSync($item->id, $this->both());

        $this->assertSame(4, $this->imageRequests()); // HEAD + GET de las dos imágenes, solo la primera vez
        $this->assertCount(2, $item->fresh()->images);
    }

    public function test_sustituye_una_imagen_que_cambia_y_borra_la_antigua(): void
    {
        $this->fakeImages();
        $item = $this->newItem();

        DownloadItemImagesJob::dispatchSync($item->id, [$this->image('https://image.tmdb.org/t/p/w780/a.jpg', 'poster')]);
        $old = $item->fresh()->images[0]['variants'];

        DownloadItemImagesJob::dispatchSync($item->id, [$this->image('https://image.tmdb.org/t/p/w780/b.jpg', 'poster')]);

        $images = $item->fresh()->images;
        $this->assertCount(1, $images);
        $this->assertNotSame($old['thumb'], $images[0]['variants']['thumb']);
        Storage::disk('public')->assertExists($images[0]['variants']['thumb']);
        Storage::disk('public')->assertMissing($old['thumb']);
        Storage::disk('public')->assertMissing($old['banner']);
    }

    public function test_si_fallan_todas_conserva_las_imagenes_actuales(): void
    {
        $this->fakeImages(['image.tmdb.org/t/p/w780/b.jpg']); // la a.jpg se descarga y la b.jpg da 404
        $item = $this->newItem();
        DownloadItemImagesJob::dispatchSync($item->id, [$this->image('https://image.tmdb.org/t/p/w780/a.jpg', 'poster')]);
        $before = $item->fresh()->images;

        Log::spy();
        DownloadItemImagesJob::dispatchSync($item->id, [$this->image('https://image.tmdb.org/t/p/w780/b.jpg', 'poster')]);

        $this->assertEquals($before, $item->fresh()->images);
        Storage::disk('public')->assertExists($before[0]['variants']['thumb']);
        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'fallaron todas las imágenes'))
            ->once();
    }

    public function test_no_hace_nada_si_el_item_no_existe(): void
    {
        Http::fake();
        $item = $this->newItem();
        $id = $item->id;
        $item->delete();

        DownloadItemImagesJob::dispatchSync($id, $this->both());

        Http::assertNothingSent();
    }

    public function test_no_descarga_de_un_host_que_resuelve_a_una_ip_privada(): void
    {
        Log::spy();
        Http::fake();
        $this->fakeHostResolver('10.0.0.1');
        $item = $this->newItem();

        DownloadItemImagesJob::dispatchSync($item->id, $this->both());

        $this->assertSame([], $item->fresh()->images);
        Http::assertNothingSent();
        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'no se pudo descargar una imagen'))
            ->twice();
    }
}
