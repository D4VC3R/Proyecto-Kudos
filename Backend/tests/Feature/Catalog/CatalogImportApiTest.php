<?php

namespace Tests\Feature\Catalog;

use App\Jobs\Catalog\DownloadItemImagesJob;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\LoadsFixtures;
use Tests\TestCase;

/**
 * F1-07: POST /api/catalog/import (importación bajo demanda de un título de la fuente externa).
 */
class CatalogImportApiTest extends TestCase
{
    use CreatesItems, LoadsFixtures, RefreshDatabase;

    private Category $peliculas;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'catalog.user_agent' => 'KudosTest/1.0 (+https://kudos.test)',
            'catalog.providers.tmdb.token' => 'token-de-prueba',
        ]);
        Sleep::fake();
        Cache::flush();
        Bus::fake([DownloadItemImagesJob::class]);

        $this->peliculas = $this->createCategory('peliculas', 'Películas');
        $this->createCategory('series', 'Series');
    }

    private function importar(string $externalId = 'movie:603', string $category = 'peliculas'): TestResponse
    {
        return $this->postJson('/api/catalog/import', ['category' => $category, 'external_id' => $externalId]);
    }

    private function fakeMovie(): void
    {
        Http::fake(['api.themoviedb.org/3/movie/603*' => Http::response($this->fixture('catalog/tmdb/movie-603-es.json'))]);
    }

    private function linkItem(array $attrs = []): Item
    {
        $item = $this->createItem($this->peliculas, $attrs);

        ItemSource::query()->create([
            'item_id' => $item->id,
            'provider' => 'tmdb',
            'external_id' => 'movie:603',
            'url' => 'https://www.themoviedb.org/movie/603',
            'is_primary' => true,
        ]);

        return $item;
    }

    private function movieRequests(): int
    {
        return Http::recorded(fn ($request) => str_contains($request->url(), '/movie/603'))->count();
    }

    public function test_sin_sesion_responde_401(): void
    {
        Http::fake();

        $this->importar()->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');

        $this->assertSame(0, Item::query()->count());
        Http::assertNothingSent();
    }

    public function test_sin_email_verificado_responde_403(): void
    {
        Http::fake();
        $this->actingAsUser(['email_verified_at' => null]);

        $this->importar()->assertForbidden();

        $this->assertSame(0, Item::query()->count());
        Http::assertNothingSent();
    }

    public function test_un_usuario_baneado_responde_403(): void
    {
        Http::fake();
        $this->actingAsUser(['is_banned' => true]);

        $this->importar()->assertForbidden();

        $this->assertSame(0, Item::query()->count());
        Http::assertNothingSent();
    }

    public function test_importa_un_titulo_nuevo_responde_201_y_encola_las_imagenes(): void
    {
        $this->fakeMovie();
        $this->actingAsUser();

        $response = $this->importar()
            ->assertStatus(201)
            ->assertJsonPath('message', 'Título añadido a Kudos.')
            ->assertJsonPath('data.name', 'Matrix')
            ->assertJsonPath('data.category.slug', 'peliculas');

        $item = Item::query()->sole();
        $this->assertSame($item->id, $response->json('data.id'));
        $this->assertNull($item->creator_id);
        $this->assertSame(Item::STATUS_ACTIVE, $item->status);

        Bus::assertDispatched(
            DownloadItemImagesJob::class,
            fn ($job) => $job->itemId === $item->id && count($job->images) === 2,
        );
    }

    public function test_si_ya_existe_responde_200_con_item_id_sin_duplicar_ni_llamar_al_proveedor(): void
    {
        Http::fake();
        $this->actingAsUser();
        $item = $this->linkItem();

        $this->importar()
            ->assertOk()
            ->assertJsonPath('message', 'Este título ya estaba en Kudos.')
            ->assertJsonPath('data.item_id', $item->id);

        $this->assertSame(1, Item::query()->count());
        Http::assertNothingSent();
        Bus::assertNotDispatched(DownloadItemImagesJob::class);
    }

    public function test_importar_dos_veces_el_mismo_titulo_no_duplica(): void
    {
        $this->fakeMovie();
        $this->actingAsUser();

        $primero = $this->importar()->assertStatus(201);
        $segundo = $this->importar()->assertOk();

        $this->assertSame($primero->json('data.id'), $segundo->json('data.item_id'));
        $this->assertSame(1, Item::query()->count());
        $this->assertSame(1, ItemSource::query()->where('provider', 'tmdb')->count());
        $this->assertSame(1, $this->movieRequests());
    }

    public function test_si_coincide_por_qid_con_un_item_existente_responde_200(): void
    {
        $this->fakeMovie();
        $this->actingAsUser();
        $item = $this->createItem($this->peliculas, ['wikidata_id' => 'Q83495']);

        $this->importar()
            ->assertOk()
            ->assertJsonPath('data.item_id', $item->id);

        $this->assertSame(1, Item::query()->count());
        Bus::assertDispatched(DownloadItemImagesJob::class);
    }

    public function test_un_titulo_existente_inactivo_o_borrado_responde_409(): void
    {
        Http::fake();
        $this->actingAsUser();
        $item = $this->linkItem(['status' => Item::STATUS_INACTIVE]);

        $this->importar()->assertStatus(409)->assertJsonPath('error.code', 'conflict');

        $this->assertSame(Item::STATUS_INACTIVE, $item->fresh()->status);

        $item->update(['status' => Item::STATUS_ACTIVE]);
        $item->delete();

        $this->importar()->assertStatus(409)->assertJsonPath('error.code', 'conflict');

        Http::assertNothingSent();
    }

    public function test_si_la_fuente_no_lo_tiene_responde_404(): void
    {
        Http::fake(['api.themoviedb.org/3/movie/999*' => Http::response($this->fixture('catalog/tmdb/not-found.json'), 404)]);
        $this->actingAsUser();

        $this->importar('movie:999')->assertNotFound()->assertJsonPath('error.code', 'not_found');

        $this->assertSame(0, Item::query()->count());
    }

    public function test_si_la_fuente_falla_responde_503_y_no_crea_nada(): void
    {
        Http::fake(['api.themoviedb.org/3/movie/603*' => Http::response([], 500)]);
        $this->actingAsUser();

        $this->importar()->assertStatus(503)->assertJsonPath('error.code', 'catalog_unavailable');

        $this->assertSame(0, Item::query()->count());
        $this->assertSame(1, $this->movieRequests());
    }

    public function test_valida_la_categoria_y_el_external_id(): void
    {
        Http::fake();
        $this->actingAsUser();
        $this->createCategory('libros', 'Libros');

        $this->importar('tv:1399')->assertStatus(422)->assertJsonPath('error.code', 'validation_error');
        $this->importar('abc')->assertStatus(422)->assertJsonPath('error.code', 'validation_error');
        $this->postJson('/api/catalog/import', ['category' => 'peliculas'])->assertStatus(422)->assertJsonPath('error.code', 'validation_error');
        $this->importar('movie:603', 'libros')->assertStatus(422)->assertJsonPath('error.code', 'validation_error');

        Category::query()->where('slug', 'peliculas')->update(['is_active' => false]);
        $this->importar()->assertStatus(422)->assertJsonPath('error.code', 'validation_error');

        Http::assertNothingSent();
    }

    public function test_limita_a_cinco_importaciones_por_minuto_y_usuario(): void
    {
        Http::fake();
        $this->actingAsUser();
        $this->linkItem();

        for ($i = 0; $i < 5; $i++) {
            $this->importar()->assertOk();
        }

        $this->importar()
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'too_many_requests')
            ->assertHeader('Retry-After');
    }

    public function test_registra_en_el_log_quien_lo_pidio(): void
    {
        $this->fakeMovie();
        $user = $this->actingAsUser();
        Log::spy();

        $this->importar()->assertStatus(201);

        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $message, array $context = []) => str_contains($message, 'bajo demanda')
                && $context['user_id'] === $user->id
                && $context['outcome'] === 'created')
            ->once();
    }
}
