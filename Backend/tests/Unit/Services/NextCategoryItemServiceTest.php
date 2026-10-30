<?php

namespace Tests\Unit\Services;

use App\Services\NextCategoryItemService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class NextCategoryItemServiceTest extends TestCase
{
    private NextCategoryItemService $service;

    private CarbonImmutable $today;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(NextCategoryItemService::class);
        $this->today = CarbonImmutable::parse('2026-06-15');
    }

    public function test_los_pesos_por_defecto_de_la_cola_son_los_de_la_ficha(): void
    {
        $weights = config('catalog.queue_weights');

        $this->assertSame(['pop' => 0.45, 'new' => 0.2, 'qual' => 0.15, 'rand' => 0.2], $weights);
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 1e-9);
    }

    public function test_recencia_vale_1_en_los_ultimos_90_dias_y_en_el_futuro(): void
    {
        $this->assertSame(1.0, $this->service->recencyScore($this->today, $this->today));
        $this->assertSame(1.0, $this->service->recencyScore($this->today->subDays(90), $this->today));
        $this->assertSame(1.0, $this->service->recencyScore($this->today->addDays(30), $this->today));
    }

    public function test_recencia_vale_0_desde_los_5_anos_y_sin_fecha(): void
    {
        $this->assertSame(0.0, $this->service->recencyScore($this->today->subYears(5), $this->today));
        $this->assertSame(0.0, $this->service->recencyScore($this->today->subYears(10), $this->today));
        $this->assertSame(0.0, $this->service->recencyScore(null, $this->today));
    }

    public function test_recencia_baja_en_linea_recta_entre_90_dias_y_5_anos(): void
    {
        $start = $this->today->subYears(5);
        $end = $this->today->subDays(90);
        $middle = $start->addDays((int) round($start->diffInDays($end) / 2));

        $this->assertEqualsWithDelta(0.5, $this->service->recencyScore($middle, $this->today), 0.01);

        $justOutside = $this->service->recencyScore($this->today->subDays(91), $this->today);
        $this->assertGreaterThan(0.99, $justOutside);
        $this->assertLessThan(1.0, $justOutside);

        $this->assertGreaterThan(
            $this->service->recencyScore($this->today->subYears(3), $this->today),
            $this->service->recencyScore($this->today->subYear(), $this->today)
        );
    }

    public function test_popularidad_normalizada_en_escala_logaritmica(): void
    {
        $this->assertSame(1.0, $this->service->popularityScore(200, 200));
        $this->assertSame(0.0, $this->service->popularityScore(0, 200));
        $this->assertSame(0.0, $this->service->popularityScore(50, 0));

        $score = $this->service->popularityScore(20, 200);
        $this->assertEqualsWithDelta(log1p(20) / log1p(200), $score, 1e-9);
        $this->assertGreaterThan(20 / 200, $score);
    }
}
