<?php

namespace Tests\Unit\Catalog\Clients;

use App\Catalog\Clients\Concerns\ThrottlesRequests;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Sleep;
use RuntimeException;
use Tests\TestCase;

class ThrottlesRequestsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tiempo congelado y sincronizado con las esperas: la ventana avanza en lugar de girar en vacío.
        $this->freezeTime();
        Sleep::fake(syncWithCarbon: true);
        Cache::flush();
    }

    private function client(): object
    {
        return new class
        {
            use ThrottlesRequests;

            public function call(string $key, int $max, int $decay): void
            {
                $this->throttle($key, $max, $decay);
            }
        };
    }

    public function test_sin_espera_maxima_espera_lo_que_haga_falta(): void
    {
        config(['catalog.http.max_throttle_wait_seconds' => null]);
        RateLimiter::hit('catalog:prueba', 60);

        $this->client()->call('catalog:prueba', 1, 60);

        Sleep::assertSleptTimes(1);
        Sleep::assertSlept(fn (CarbonInterval $duration) => (int) $duration->totalSeconds === 60);
    }

    public function test_con_espera_maxima_lanza_runtime_exception_sin_dormir(): void
    {
        config(['catalog.http.max_throttle_wait_seconds' => 5]);
        RateLimiter::hit('catalog:prueba', 60);

        $exception = null;

        try {
            $this->client()->call('catalog:prueba', 1, 60);
        } catch (RuntimeException $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertStringContainsString('catalog:prueba', $exception->getMessage());
        Sleep::assertNeverSlept();
        $this->assertSame(1, RateLimiter::attempts('catalog:prueba'));
    }

    public function test_con_espera_maxima_espera_si_cabe(): void
    {
        config(['catalog.http.max_throttle_wait_seconds' => 5]);
        RateLimiter::hit('catalog:prueba', 2);

        $this->client()->call('catalog:prueba', 1, 2);

        Sleep::assertSleptTimes(1);
    }
}
