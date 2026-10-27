<?php

namespace Tests\Feature\Items;

use App\Jobs\RecalculateItemQualityJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class RecalculateItemQualityCommandTest extends TestCase
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

        $this->artisan('items:recalculate-quality', ['--chunk' => 2])->assertSuccessful();

        Queue::assertPushedOn('catalog', RecalculateItemQualityJob::class);
        Queue::assertPushed(RecalculateItemQualityJob::class, 2);

        $queued = Queue::pushed(RecalculateItemQualityJob::class)
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

        $this->artisan('items:recalculate-quality', ['--category' => 'series'])->assertSuccessful();

        Queue::assertPushed(RecalculateItemQualityJob::class, 1);
        $jobs = Queue::pushed(RecalculateItemQualityJob::class);
        $this->assertSame([$serie->id], $jobs->first()->itemIds);
    }

    public function test_el_comando_falla_si_la_categoria_no_existe(): void
    {
        Queue::fake();

        $this->artisan('items:recalculate-quality', ['--category' => 'no-existe'])->assertFailed();

        Queue::assertNothingPushed();
    }

    public function test_el_comando_calcula_la_calidad_de_items_sembrados_sin_eventos(): void
    {
        // Sin Queue::fake(): la cola es 'sync' en los tests y el job se ejecuta al encolarse.
        $category = $this->createCategory();
        $good = $this->createItem($category, ['name' => 'Ficha completa']);
        $poor = $this->createItem($category, ['name' => 'Ficha pobre', 'images' => [], 'description' => 'Item importado.']);

        // Simula el sembrado: ítems creados sin eventos, con quality_score = 0.
        DB::table('items')->update(['quality_score' => 0]);

        $this->artisan('items:recalculate-quality')->assertSuccessful();

        $this->assertSame(80, $good->fresh()->quality_score);
        $this->assertSame(0, $poor->fresh()->quality_score);
    }

    public function test_el_recalculo_de_calidad_no_toca_updated_at(): void
    {
        $item = $this->createItem($this->createCategory());
        $updatedAt = $item->fresh()->updated_at;
        DB::table('items')->where('id', $item->id)->update(['quality_score' => 0]);

        $this->travel(1)->days();
        $this->artisan('items:recalculate-quality')->assertSuccessful();

        $fresh = $item->fresh();
        $this->assertEquals($updatedAt, $fresh->updated_at);
        $this->assertSame(80, $fresh->quality_score);
    }

    public function test_el_recalculo_de_calidad_esta_programado_semanalmente(): void
    {
        // schedule:list carga routes/console.php, donde se registra la programación.
        Artisan::call('schedule:list');

        $event = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains((string) $e->command, 'items:recalculate-quality'));

        $this->assertNotNull($event);
        $this->assertSame('30 3 * * 1', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }
}
