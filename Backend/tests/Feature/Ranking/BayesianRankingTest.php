<?php

namespace Tests\Feature\Ranking;

use App\Jobs\RecalculateItemStatsJob;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use App\Services\ItemStatsService;
use Database\Seeders\VoteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class BayesianRankingTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config(['kudos.ranking.bayes_min_votes' => 5, 'kudos.ranking.category_mean_fallback' => 5.0]);
    }

    /**
     * Crea la categoría 'peliculas' con tres ítems: X (un 10), Y (20 votos de 8.0) y Z (30 votos de 4.5).
     * Con eso C = 305 / 51, unos 5.98.
     *
     * @return array{0: Category, 1: Item, 2: Item, 3: Item}
     */
    private function createXyzScenario(): array
    {
        $category = $this->createCategory('peliculas', 'Películas');
        $users = User::factory()->count(30)->create()->values();

        $x = $this->createItem($category, ['name' => 'Un diez']);
        $y = $this->createItem($category, ['name' => 'Veinte ochos']);
        $z = $this->createItem($category, ['name' => 'Relleno']);

        Vote::factory()->byUser($users[0])->forItem($x)->rated(10.0)->create();
        foreach ($users->take(20) as $user) {
            Vote::factory()->byUser($user)->forItem($y)->rated(8.0)->create();
        }
        foreach ($users as $user) {
            Vote::factory()->byUser($user)->forItem($z)->rated(4.5)->create();
        }

        return [$category, $x, $y, $z];
    }

    public function test_la_formula_bayesiana_penaliza_los_items_con_pocos_votos(): void
    {
        $service = app(ItemStatsService::class);

        $unDiez = $service->bayesScore(1, 10.0, 6.0);
        $veinteOchos = $service->bayesScore(20, 8.0, 6.0);

        $this->assertEqualsWithDelta(6.6667, $unDiez, 0.0001);
        $this->assertEqualsWithDelta(7.6, $veinteOchos, 0.0001);
        $this->assertLessThan($veinteOchos, $unDiez);
        $this->assertSame(0.0, $service->bayesScore(0, 0.0, 6.0));
    }

    public function test_un_unico_diez_queda_por_debajo_de_veinte_votos_con_media_ocho(): void
    {
        [$category, $x, $y, $z] = $this->createXyzScenario();

        $this->artisan('items:recalculate-stats')->assertSuccessful();

        $this->assertEqualsWithDelta(6, app(ItemStatsService::class)->categoryMean($category->id), 0.1);
        $this->assertEqualsWithDelta(6.6503, $x->fresh()->bayes_score, 0.0001);
        $this->assertEqualsWithDelta(7.5961, $y->fresh()->bayes_score, 0.0001);
        $this->assertLessThan($y->fresh()->bayes_score, $x->fresh()->bayes_score);

        $response = $this->getJson('/api/categories/peliculas/ranking');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['ranking' => [['id', 'name', 'vote_avg', 'vote_count']]]]);
        $ranking = collect($response->json('data.ranking'));
        $this->assertSame([$y->id, $x->id, $z->id], $ranking->pluck('id')->all());
        $this->assertEquals(10, $ranking->firstWhere('id', $x->id)['vote_avg']);
        $this->assertEquals(8, $ranking->firstWhere('id', $y->id)['vote_avg']);
    }

    public function test_el_listado_ordenado_por_vote_avg_usa_bayes_score(): void
    {
        [, $x, $y] = $this->createXyzScenario();

        $this->artisan('items:recalculate-stats')->assertSuccessful();

        $response = $this->getJson('/api/items?category_slug=peliculas&sort_by=vote_avg');

        $response->assertOk();
        $data = collect($response->json('data'));
        $ids = $data->pluck('id')->all();
        $this->assertLessThan(array_search($x->id, $ids), array_search($y->id, $ids));
        foreach ($data as $element) {
            $this->assertArrayHasKey('vote_avg', $element);
            $this->assertArrayHasKey('vote_count', $element);
        }
    }

    public function test_los_items_sin_votos_quedan_al_final(): void
    {
        $category = $this->createCategory('peliculas', 'Películas');
        $user = User::factory()->create();
        $sinVotos = $this->createItem($category, ['name' => 'Aaa sin votos']);
        $mmm = $this->createItem($category, ['name' => 'Mmm']);
        $zzz = $this->createItem($category, ['name' => 'Zzz']);
        Vote::factory()->byUser($user)->forItem($mmm)->rated(2.0)->create();
        Vote::factory()->byUser($user)->forItem($zzz)->rated(3.0)->create();

        $this->artisan('items:recalculate-stats')->assertSuccessful();

        $ranking = $this->getJson('/api/categories/peliculas/ranking')->assertOk()->json('data.ranking');
        $this->assertSame($sinVotos->id, collect($ranking)->last()['id']);

        $listado = $this->getJson('/api/items?category_slug=peliculas&sort_by=vote_avg')->assertOk()->json('data');
        $this->assertSame($sinVotos->id, collect($listado)->last()['id']);

        $this->assertSame(0.0, $sinVotos->fresh()->bayes_score);
    }

    public function test_votar_recalcula_bayes_score_con_la_media_cacheada(): void
    {
        $category = $this->createCategory('peliculas', 'Películas');
        $item = $this->createItem($category);

        $this->actingAsUser();
        $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'vote', 'score' => 8.0])->assertCreated();
        $this->assertEqualsWithDelta(8.0, $item->fresh()->bayes_score, 0.0001);

        $this->actingAsUser();
        $this->postJson('/api/votes', ['item_id' => $item->id, 'type' => 'vote', 'score' => 6.0])->assertCreated();
        // C sigue cacheada en 8: (2 · 7 + 5 · 8) / 7.
        $this->assertEqualsWithDelta(7.7143, $item->fresh()->bayes_score, 0.0001);

        $this->assertTrue(Cache::has(ItemStatsService::categoryMeanCacheKey($category->id)));
    }

    public function test_la_media_de_la_categoria_cae_al_fallback_y_se_cachea_una_hora(): void
    {
        $service = app(ItemStatsService::class);
        $category = $this->createCategory('peliculas', 'Películas');
        $item = $this->createItem($category);

        $this->assertSame(5.0, $service->categoryMean($category->id));
        $this->assertTrue(Cache::has(ItemStatsService::categoryMeanCacheKey($category->id)));

        $users = User::factory()->count(2)->create();
        foreach ($users as $user) {
            Vote::factory()->byUser($user)->forItem($item)->rated(8.0)->create();
        }
        $this->assertSame(5.0, $service->categoryMean($category->id));

        $this->travel(61)->minutes();
        $this->assertSame(8.0, $service->categoryMean($category->id));

        $this->assertSame(5.0, $service->categoryMean(null));
    }

    public function test_el_comando_recalcula_la_media_de_la_categoria_antes_de_encolar(): void
    {
        Queue::fake();
        $service = app(ItemStatsService::class);
        $category = $this->createCategory('peliculas', 'Películas');
        $item = $this->createItem($category);
        $key = ItemStatsService::categoryMeanCacheKey($category->id);

        $this->assertSame(5.0, $service->categoryMean($category->id));
        foreach (User::factory()->count(2)->create() as $user) {
            Vote::factory()->byUser($user)->forItem($item)->rated(8.0)->create();
        }

        $this->artisan('items:recalculate-stats', ['--category' => 'peliculas'])->assertSuccessful();

        $this->assertSame(8.0, Cache::get($key));
        Queue::assertPushed(RecalculateItemStatsJob::class);
    }

    public function test_el_seeder_de_votos_calcula_bayes_score(): void
    {
        $category = $this->createCategory('peliculas', 'Películas');
        User::factory()->count(5)->create();
        foreach (range(1, 3) as $n) {
            $this->createItem($category, ['name' => "Ítem {$n}"]);
        }

        $this->seed(VoteSeeder::class);

        $service = app(ItemStatsService::class);
        $items = Item::query()->where('vote_count', '>', 0)->get();
        $this->assertNotEmpty($items);
        foreach ($items as $item) {
            $expected = $service->bayesScore(
                (int) $item->vote_count,
                (float) $item->vote_avg,
                $service->categoryMean($item->category_id)
            );
            $this->assertEqualsWithDelta($expected, $item->bayes_score, 0.0001);
        }
    }
}
