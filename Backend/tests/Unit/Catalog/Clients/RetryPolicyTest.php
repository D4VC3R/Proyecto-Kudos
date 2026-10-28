<?php

namespace Tests\Unit\Catalog\Clients;

use App\Catalog\Clients\RetryPolicy;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Tests\TestCase;

class RetryPolicyTest extends TestCase
{
    private function exceptionWithStatus(int $status): RequestException
    {
        return new RequestException(new Response(new PsrResponse($status)));
    }

    public function test_reintenta_ante_429_errores_del_servidor_y_fallos_de_conexion(): void
    {
        $this->assertTrue(RetryPolicy::shouldRetry($this->exceptionWithStatus(429)));
        $this->assertTrue(RetryPolicy::shouldRetry($this->exceptionWithStatus(500)));
        $this->assertTrue(RetryPolicy::shouldRetry($this->exceptionWithStatus(503)));
        $this->assertTrue(RetryPolicy::shouldRetry(new ConnectionException('timeout')));
    }

    public function test_no_reintenta_ante_404_ni_otros_errores_del_cliente(): void
    {
        $this->assertFalse(RetryPolicy::shouldRetry($this->exceptionWithStatus(404)));
        $this->assertFalse(RetryPolicy::shouldRetry($this->exceptionWithStatus(400)));
        $this->assertFalse(RetryPolicy::shouldRetry($this->exceptionWithStatus(401)));
        $this->assertFalse(RetryPolicy::shouldRetry(null));
    }

    public function test_la_espera_crece_con_cada_intento(): void
    {
        config(['catalog.http.retry_sleep_ms' => 500]);

        $this->assertSame(500, RetryPolicy::backoffMilliseconds(1));
        $this->assertSame(1000, RetryPolicy::backoffMilliseconds(2));
    }
}
