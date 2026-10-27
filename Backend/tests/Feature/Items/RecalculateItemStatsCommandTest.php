<?php

namespace Tests\Feature\Items;

use App\Jobs\RecalculateItemStatsJob;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class RecalculateItemStatsCommandTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_el_comando_encola_un_job_por_bloque_en_la_cola_catalog(): void
    {
        Queue::fake();
        $category = $this->createCategory();
        $ids = collect([
            $this->createItem($category)->id,
            $this->createItem($category)->id,
            $this->createItem($category)->id,
        ]);

        $this->artisan('items:recalculate-stats', ['--chunk' => 2])->assertSuccessful();

        Queue::assertPushedOn('catalog', RecalculateItemStatsJob::class);
        Queue::assertPushed(RecalculateItemStatsJob::class, 2);

        $queued = Queue::pushed(RecalculateItemStatsJob::class)
            ->flatMap(fn ($job) => $job->itemIds)
            ->sort()
            ->values()
            ->all();
        $this->assertSame($ids->sort()->values()->all(), $queued);
    }

    public function test_el_comando_filtra_por_categoria(): void
    {
        Queue::fake();
        $peliculas = $this->createCategory();
        $series = $this->createCategory('series', 'Series');
        $this->createItem($peliculas);
        $this->createItem($peliculas);
        $serie = $this->createItem($series);

        $this->artisan('items:recalculate-stats', ['--category' => 'series'])->assertSuccessful();

        Queue::assertPushed(RecalculateItemStatsJob::class, 1);
        $jobs = Queue::pushed(RecalculateItemStatsJob::class);
        $this->assertSame([$serie->id], $jobs->first()->itemIds);
    }

    public function test_el_comando_falla_si_la_categoria_no_existe(): void
    {
        Queue::fake();

        $this->artisan('items:recalculate-stats', ['--category' => 'no-existe'])->assertFailed();

        Queue::assertNothingPushed();
    }

    public function test_el_comando_corrige_las_estadisticas_desfasadas(): void
    {
        // Sin Queue::fake(): la cola es 'sync' en los tests y el job se ejecuta al encolarse.
        $category = $this->createCategory();
        $item1 = $this->createItem($category, ['vote_avg' => 3, 'vote_count' => 10]);
        $item2 = $this->createItem($category, ['vote_avg' => 6, 'vote_count' => 4]);

        [$u1, $u2, $u3] = User::factory()->count(3)->create()->all();
        Vote::factory()->byUser($u1)->forItem($item1)->rated(7.5)->create();
        Vote::factory()->byUser($u2)->forItem($item1)->rated(8.0)->create();
        Vote::factory()->byUser($u3)->forItem($item1)->create(['type' => 'skip', 'score' => null]);

        $this->artisan('items:recalculate-stats')->assertSuccessful();

        $item1 = $item1->fresh();
        $this->assertEquals(7.75, (float) $item1->vote_avg);
        $this->assertSame(2, (int) $item1->vote_count);

        $item2 = $item2->fresh();
        $this->assertEquals(0, (float) $item2->vote_avg);
        $this->assertSame(0, (int) $item2->vote_count);
    }

    public function test_el_recalculo_esta_programado_a_diario_a_las_tres(): void
    {
        // schedule:list carga routes/console.php, donde se registra la programación.
        Artisan::call('schedule:list');

        $event = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains((string) $e->command, 'items:recalculate-stats'));

        $this->assertNotNull($event);
        $this->assertSame('0 3 * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }
}
