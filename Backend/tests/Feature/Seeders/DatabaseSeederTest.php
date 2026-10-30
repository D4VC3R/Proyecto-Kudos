<?php

namespace Tests\Feature\Seeders;

use App\Jobs\Catalog\DownloadItemImagesJob;
use App\Models\Category;
use App\Models\Item;
use App\Models\Proposal;
use App\Models\User;
use App\Models\Vote;
use Database\Seeders\VoteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\FakesRemoteImages;
use Tests\Concerns\LoadsFixtures;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use CreatesItems, FakesRemoteImages, LoadsFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
        $this->fakeHostResolver();
        Sleep::fake();
        Cache::flush();

        config([
            'kudos.seed_admin' => ['name' => 'kudosAdmin', 'email' => 'admin@kudos.test', 'password' => 'contrasena-de-prueba'],
            'catalog.user_agent' => 'KudosTest/1.0 (+https://kudos.test)',
            'catalog.providers.tmdb.token' => 'token-de-prueba',
        ]);
    }

    /** Falsea las portadas que descarga CategorySeeder (un PNG nuevo por petición), más las reglas extra. */
    private function fakeHttp(array $extra = []): void
    {
        $png = $this->fakePng();
        $cover = fn () => Http::response($png, 200, ['Content-Type' => 'image/png']);

        Http::fake($extra + [
            'sm.ign.com/*' => $cover,               // videojuegos
            'm.media-amazon.com/*' => $cover,       // peliculas
            'images.bauerhosting.com/*' => $cover,  // series
        ]);
    }

    /** Página de un listado de TMDB sin genre_ids (así no se pide la lista de géneros). */
    private function listPage(array $ids, string $titleField = 'title'): array
    {
        return [
            'page' => 1,
            'total_pages' => 1,
            'total_results' => count($ids),
            'results' => array_map(fn (int $id) => ['id' => $id, $titleField => "Título {$id}", 'genre_ids' => [], 'popularity' => 1.0], $ids),
        ];
    }

    /** Pasa la aplicación al entorno local (VoteSeeder y los 50 usuarios de prueba solo existen ahí). */
    private function actAsLocal(): void
    {
        $this->app['env'] = 'local';
        if (! $this->app->isLocal()) {
            $this->app->detectEnvironment(fn () => 'local');
        }
    }

    public function test_fuera_de_local_siembra_categorias_roles_y_solo_el_admin(): void
    {
        $this->fakeHttp();

        $this->seed();

        $this->assertSame(4, Category::query()->count());
        $this->assertSame(0, Item::query()->count());
        $this->assertSame(0, Proposal::query()->count());
        $this->assertSame(0, Vote::query()->count());
        $this->assertTrue(Role::query()->where('name', 'admin')->exists());
        $this->assertTrue(Role::query()->where('name', 'user')->exists());
        $this->assertSame(1, User::query()->count());
        $this->assertTrue(User::query()->where('email', 'admin@kudos.test')->firstOrFail()->hasRole('admin'));
    }

    public function test_en_local_crea_tambien_los_50_usuarios_de_prueba(): void
    {
        $this->fakeHttp();
        $this->actAsLocal();

        $this->seed();

        $this->assertSame(51, User::query()->count());
        $this->assertSame(50, User::role('user')->count());
        $this->assertTrue(User::query()->where('email', 'admin@kudos.test')->firstOrFail()->hasRole('admin'));
    }

    public function test_fuera_de_local_no_siembra_votos(): void
    {
        $this->fakeHttp();
        $category = $this->createCategory('peliculas', 'Películas');
        $this->createItem($category);

        $this->seed();

        $this->assertFalse(app()->isLocal());
        $this->assertSame(0, Vote::query()->count());
    }

    public function test_en_local_siembra_votos(): void
    {
        $this->fakeHttp();
        $category = $this->createCategory('peliculas', 'Películas');
        $item = $this->createItem($category);
        $this->actAsLocal();

        $this->seed();

        $this->assertGreaterThan(0, Vote::query()->count());
        $this->assertGreaterThan(0, $item->fresh()->vote_count);
    }

    public function test_vote_seeder_se_niega_a_ejecutarse_fuera_de_local(): void
    {
        $category = $this->createCategory('peliculas', 'Películas');
        $this->createItem($category);
        User::factory()->count(3)->create();

        try {
            app(VoteSeeder::class)->run();
            $this->fail('VoteSeeder no debería ejecutarse fuera de local.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('solo se ejecuta en local', $e->getMessage());
        }

        $this->assertSame(0, Vote::query()->count());
        $this->assertSame(0, DB::table('kudos_transactions')->count());
    }

    public function test_vote_seeder_tambien_se_niega_en_produccion(): void
    {
        $category = $this->createCategory('peliculas', 'Películas');
        $this->createItem($category);
        User::factory()->count(3)->create();
        $this->app['env'] = 'production';

        try {
            app(VoteSeeder::class)->run();
            $this->fail('VoteSeeder no debería ejecutarse en producción.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('solo se ejecuta en local', $e->getMessage());
        }

        $this->assertSame(0, Vote::query()->count());
        $this->assertSame(0, DB::table('kudos_transactions')->count());
    }

    public function test_la_secuencia_de_desarrollo_funciona_con_un_proveedor_falso(): void
    {
        Bus::fake([DownloadItemImagesJob::class]);
        $this->fakeHttp([
            'api.themoviedb.org/3/movie/now_playing*' => Http::response($this->listPage([603])),
            'api.themoviedb.org/3/movie/upcoming*' => Http::response($this->listPage([])),
            'api.themoviedb.org/3/movie/popular*' => Http::response($this->listPage([])),
            'api.themoviedb.org/3/movie/603*' => Http::response($this->fixture('catalog/tmdb/movie-603-es.json')),
            'api.themoviedb.org/3/tv/on_the_air*' => Http::response($this->listPage([1399], 'name')),
            'api.themoviedb.org/3/tv/popular*' => Http::response($this->listPage([], 'name')),
            'api.themoviedb.org/3/tv/1399*' => Http::response($this->fixture('catalog/tmdb/tv-1399-es.json')),
        ]);

        // La secuencia de desarrollo es en local (50 usuarios de prueba y VoteSeeder permitido).
        $this->actAsLocal();
        // 1. sail artisan migrate:fresh --seed (RefreshDatabase ya deja el esquema limpio; VoteSeeder avisa: aún no hay ítems)
        $this->seed();
        // 2. sail artisan catalog:fetch-new --all --limit=50
        $this->artisan('catalog:fetch-new', ['--all' => true, '--limit' => 5])->assertSuccessful();
        // 3. sail artisan db:seed --class=VoteSeeder
        $this->seed(VoteSeeder::class);

        $this->assertSame(51, User::query()->count());
        $this->assertSame(2, Item::query()->count());
        $this->assertSame(1, Item::query()->whereHas('category', fn ($q) => $q->where('slug', 'peliculas'))->count());
        $this->assertSame(1, Item::query()->whereHas('category', fn ($q) => $q->where('slug', 'series'))->count());
        $this->assertSame(0, Item::query()->whereHas('category', fn ($q) => $q->whereIn('slug', ['videojuegos', 'destinos']))->count());
        $this->assertFalse(Item::query()->whereNull('slug')->exists());
        $this->assertSame(2, Item::query()->where('vote_count', '>', 0)->count());
        $this->assertGreaterThan(0, Vote::query()->count());

        $movies = $this->getJson('/api/items?category_slug=peliculas')->assertOk();
        $this->assertSame(['year', 'genres'], array_keys($movies->json('data.0.attributes')));

        $categories = $this->getJson('/api/categories')->assertOk();
        $this->assertSame(['peliculas', 'series'], collect($categories->json('data'))->pluck('slug')->all());
    }
}
