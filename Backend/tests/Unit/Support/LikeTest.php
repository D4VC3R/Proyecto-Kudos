<?php

namespace Tests\Unit\Support;

use App\Support\Like;
use PHPUnit\Framework\TestCase;

class LikeTest extends TestCase
{
    public function test_escapa_porcentaje_guion_bajo_y_barra_invertida(): void
    {
        $this->assertSame('100\%', Like::escape('100%'));
        $this->assertSame('snake\_case', Like::escape('snake_case'));
        $this->assertSame('C:\\\\Juegos', Like::escape('C:\Juegos'));
        $this->assertSame('\\\\\%', Like::escape('\%'));
    }

    public function test_deja_intacto_el_texto_sin_comodines(): void
    {
        $this->assertSame('Zelda', Like::escape('Zelda'));
    }

    public function test_contains_envuelve_el_valor_escapado(): void
    {
        $this->assertSame('%50\%%', Like::contains('50%'));
    }
}
