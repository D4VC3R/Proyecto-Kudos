<?php

namespace Tests\Feature\Catalog;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CatalogScheduleTest extends TestCase
{
    public function test_las_tareas_del_catalogo_estan_programadas(): void
    {
        // schedule:list carga routes/console.php, donde se registra la programación.
        Artisan::call('schedule:list');
        $events = collect(app(Schedule::class)->events());

        $expected = [
            'catalog:fetch-new --all' => '0 4 * * *',
            'catalog:refresh' => '0 5 * * *',
            'catalog:purge-stale' => '30 5 * * 0',
        ];

        foreach ($expected as $command => $expression) {
            $event = $events->first(fn ($e) => str_contains((string) $e->command, $command));

            $this->assertNotNull($event, "Falta la tarea programada {$command}");
            $this->assertSame($expression, $event->expression, $command);
            $this->assertTrue($event->withoutOverlapping, "{$command} debe llevar withoutOverlapping()");
        }
    }
}
