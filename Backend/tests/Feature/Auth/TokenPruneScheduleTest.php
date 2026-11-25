<?php

namespace Tests\Feature\Auth;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TokenPruneScheduleTest extends TestCase
{
    public function test_la_purga_de_tokens_caducados_esta_programada_a_diario(): void
    {
        // schedule:list carga routes/console.php, donde se registra la programación.
        Artisan::call('schedule:list');
        $events = collect(app(Schedule::class)->events());

        $event = $events->first(fn ($e) => str_contains((string) $e->command, 'sanctum:prune-expired --hours=24'));

        $this->assertNotNull($event, 'Falta la purga programada de tokens de Sanctum');
        $this->assertSame('30 2 * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping, 'La purga debe llevar withoutOverlapping()');
    }
}
