<?php

namespace Tests\Feature\Legal;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Datos públicos de las páginas legales (F3-07): GET /api/legal.
 */
class LegalInfoTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_legal_devuelve_el_correo_de_contacto_sin_sesion(): void
    {
        config(['kudos.legal.contact_email' => 'legal@kudos.test']);

        $this->getJson('/api/legal')
            ->assertOk()
            ->assertExactJson(['data' => ['contact_email' => 'legal@kudos.test']]);
    }

    public function test_get_legal_devuelve_null_sin_correo_configurado(): void
    {
        config(['kudos.legal.contact_email' => null]);

        $this->getJson('/api/legal')
            ->assertOk()
            ->assertExactJson(['data' => ['contact_email' => null]]);
    }
}
