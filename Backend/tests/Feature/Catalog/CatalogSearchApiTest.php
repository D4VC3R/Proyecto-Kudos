<?php

namespace Tests\Feature\Catalog;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\LoadsFixtures;
use Tests\TestCase;

/**
 * F1-07: GET /api/catalog/search (candidatos de la fuente externa, marcados con exists e item_id).
 */
class CatalogSearchApiTest extends TestCase
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

        $this->peliculas = $this->createCategory('peliculas', 'Películas');
        $this->createCategory('series', 'Series');
    }

    private function fakeSearch(): void
    {
        Http::fake([
            'api.themoviedb.org/3/search/movie*' => Http::response($this->fixture('catalog/tmdb/search-movie-matrix-es.json')),
            'api.themoviedb.org/3/genre/movie/list*' => Http::response($this->fixture('catalog/tmdb/genre-movie-list-es.json')),
        ]);
    }

    private function search(string $q = 'matrix', string $category = 'peliculas'): TestResponse
    {
        return $this->getJson('/api/catalog/search?'.http_build_query(['category' => $category, 'q' => $q]));
    }

    private function linkItem(string $externalId = 'movie:603', array $attrs = []): Item
    {
        $item = $this->createItem($this->peliculas, $attrs);

        ItemSource::query()->create([
            'item_id' => $item->id,
            'provider' => 'tmdb',
            'external_id' => $externalId,
            'url' => 'https://www.themoviedb.org/movie/'.substr($externalId, 6),
            'is_primary' => true,
        ]);

        return $item;
    }

    private function searchRequests(): int
    {
        return Http::recorded(fn ($request) => str_contains($request->url(), '/search/movie'))->count();
    }

    public function test_sin_sesion_responde_401(): void
    {
        Http::fake();

        $this->search()->assertUnauthorized()->assertJsonPath('error.code', 'unauthenticated');

        Http::assertNothingSent();
    }

    public function test_sin_email_verificado_responde_403(): void
    {
        Http::fake();
        $this->actingAsUser(['email_verified_at' => null]);

        $this->search()->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_un_usuario_baneado_responde_403(): void
    {
        Http::fake();
        $this->actingAsUser(['is_banned' => true]);

        $this->search()->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_devuelve_los_candidatos_del_proveedor(): void
    {
        $this->fakeSearch();
        $this->actingAsUser();

        $this->search()
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.external_id', 'movie:603')
            ->assertJsonPath('data.0.name', 'Matrix')
            ->assertJsonPath('data.0.original_title', 'The Matrix')
            ->assertJsonPath('data.0.released_at', '1999-03-31')
            ->assertJsonPath('data.0.year', 1999)
            ->assertJsonPath('data.0.source_url', 'https://www.themoviedb.org/movie/603')
            ->assertJsonPath('data.0.exists', false)
            ->assertJsonPath('data.0.item_id', null)
            ->assertJsonPath('meta.category', 'peliculas')
            ->assertJsonStructure(['data' => [[
                'external_id', 'name', 'original_title', 'released_at', 'year', 'description', 'source_url', 'exists', 'item_id',
            ]]]);
    }

    public function test_marca_los_candidatos_que_ya_existen_con_su_item_id(): void
    {
        $this->fakeSearch();
        $this->actingAsUser();
        $item = $this->linkItem('movie:603');

        $this->search()
            ->assertOk()
            ->assertJsonPath('data.0.exists', true)
            ->assertJsonPath('data.0.item_id', $item->id)
            ->assertJsonPath('data.1.exists', false)
            ->assertJsonPath('data.1.item_id', null);
    }

    public function test_un_candidato_inactivo_o_borrado_existe_pero_sin_item_id(): void
    {
        $this->fakeSearch();
        $this->actingAsUser();
        $item = $this->linkItem('movie:603', ['status' => Item::STATUS_INACTIVE]);

        $this->search()
            ->assertOk()
            ->assertJsonPath('data.0.exists', true)
            ->assertJsonPath('data.0.item_id', null);

        $item->update(['status' => Item::STATUS_ACTIVE]);
        $item->delete();

        $this->search()
            ->assertOk()
            ->assertJsonPath('data.0.exists', true)
            ->assertJsonPath('data.0.item_id', null);
    }

    public function test_cachea_la_busqueda_diez_minutos_por_categoria_y_texto(): void
    {
        $this->fakeSearch();
        $this->actingAsUser();

        $this->search('matrix')->assertOk();
        $this->search('  MATRIX ')->assertOk();

        $this->assertSame(1, $this->searchRequests());

        $this->travel(11)->minutes();
        $this->search('matrix')->assertOk();

        $this->assertSame(2, $this->searchRequests());
    }

    public function test_exists_no_sale_de_la_cache(): void
    {
        $this->fakeSearch();
        $this->actingAsUser();

        $this->search()->assertOk()->assertJsonPath('data.0.exists', false);

        $item = $this->linkItem('movie:603');

        $this->search()
            ->assertOk()
            ->assertJsonPath('data.0.exists', true)
            ->assertJsonPath('data.0.item_id', $item->id);

        $this->assertSame(1, $this->searchRequests());
    }

    public function test_valida_la_categoria_y_el_texto(): void
    {
        Http::fake();
        $this->actingAsUser();
        $this->createCategory('libros', 'Libros');

        $this->getJson('/api/catalog/search?category=peliculas')->assertStatus(422)->assertJsonPath('error.code', 'validation_error');
        $this->search('m')->assertStatus(422)->assertJsonPath('error.code', 'validation_error');
        $this->search(str_repeat('a', 101))->assertStatus(422)->assertJsonPath('error.code', 'validation_error');
        $this->search('matrix', 'libros')->assertStatus(422)->assertJsonPath('error.code', 'validation_error');
        $this->search('matrix', 'inexistente')->assertStatus(422)->assertJsonPath('error.code', 'validation_error');

        Category::query()->where('slug', 'peliculas')->update(['is_active' => false]);
        $this->search()->assertStatus(422)->assertJsonPath('error.code', 'validation_error');

        Http::assertNothingSent();
    }

    public function test_si_el_proveedor_falla_responde_503_y_no_lo_cachea(): void
    {
        // Un segundo Http::fake() no sustituye al primero: la secuencia falla una vez y luego responde bien.
        Http::fake([
            'api.themoviedb.org/3/search/movie*' => Http::sequence()
                ->push([], 500)
                ->push($this->fixture('catalog/tmdb/search-movie-matrix-es.json')),
            'api.themoviedb.org/3/genre/movie/list*' => Http::response($this->fixture('catalog/tmdb/genre-movie-list-es.json')),
        ]);
        $this->actingAsUser();

        $this->search()->assertStatus(503)->assertJsonPath('error.code', 'catalog_unavailable');

        $this->search()->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_bajo_demanda_hace_un_solo_intento_y_restaura_la_configuracion(): void
    {
        Http::fake(['api.themoviedb.org/3/search/movie*' => Http::response([], 500)]);
        $this->actingAsUser();

        $this->search()->assertStatus(503);

        $this->assertSame(1, $this->searchRequests());
        $this->assertSame(10, config('catalog.http.timeout_seconds'));
        $this->assertSame(3, config('catalog.http.retry_times'));
    }

    public function test_sin_token_responde_503(): void
    {
        Http::fake();
        config(['catalog.providers.tmdb.token' => null]);
        $this->actingAsUser();

        $this->search()->assertStatus(503)->assertJsonPath('error.code', 'catalog_unavailable');

        Http::assertNothingSent();
    }

    public function test_limita_a_diez_busquedas_por_minuto_y_usuario(): void
    {
        $this->fakeSearch();
        $this->actingAsUser();

        for ($i = 0; $i < 10; $i++) {
            $this->search()->assertOk();
        }

        $this->search()
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'too_many_requests')
            ->assertHeader('Retry-After');

        $this->actingAsUser();

        $this->search()->assertOk();
    }
}
