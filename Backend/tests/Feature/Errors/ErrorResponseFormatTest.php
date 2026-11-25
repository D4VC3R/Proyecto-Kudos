<?php

namespace Tests\Feature\Errors;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/**
 * Contrato de errores de la API: 404 de modelo y de ruta, 405, 429 y 500 con el formato
 * {"error": {"code", "message"}} de bootstrap/app.php.
 */
class ErrorResponseFormatTest extends TestCase
{
    use RefreshDatabase;

    private function registerFailingRoute(): void
    {
        Route::get('/api/pruebas/error-inesperado', fn () => throw new RuntimeException('detalle interno de la prueba'));
    }

    public function test_un_404_de_modelo_responde_not_found_con_el_mensaje_del_modelo(): void
    {
        $this->getJson('/api/items/'.Str::uuid())
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found')
            ->assertJsonPath('error.message', 'El ítem no existe.');
    }

    public function test_una_categoria_inexistente_responde_not_found(): void
    {
        $this->getJson('/api/categories/no-existe')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found')
            ->assertJsonPath('error.message', 'La categoría no existe.');
    }

    public function test_una_ruta_inexistente_sigue_respondiendo_route_not_found(): void
    {
        $this->getJson('/api/no-existe-esta-ruta')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'route_not_found')
            ->assertJsonPath('error.message', 'La ruta solicitada no existe.');
    }

    public function test_un_metodo_no_permitido_responde_405_con_el_formato_de_la_api(): void
    {
        $response = $this->getJson('/api/login')
            ->assertStatus(405)
            ->assertJsonPath('error.code', 'method_not_allowed')
            ->assertJsonMissingPath('message');

        $this->assertNotEmpty($response->json('error.message'));
        $this->assertStringContainsString('POST', (string) $response->headers->get('Allow'));
    }

    public function test_el_limite_del_login_responde_429_con_el_formato_de_la_api(): void
    {
        User::factory()->create(['email' => 'ana@kudos.test']);

        foreach (range(1, 5) as $intento) {
            $this->postJson('/api/login', ['email' => 'ana@kudos.test', 'password' => 'incorrecta'])
                ->assertStatus(401);
        }

        $response = $this->postJson('/api/login', ['email' => 'ana@kudos.test', 'password' => 'incorrecta'])
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'too_many_requests')
            ->assertHeader('Retry-After');

        $this->assertStringStartsWith('Demasiados intentos de inicio de sesión', $response->json('error.message'));
    }

    public function test_un_throttle_sin_nombre_responde_429_con_el_formato_de_la_api(): void
    {
        Notification::fake();
        Sanctum::actingAs(User::factory()->unverified()->create());

        foreach (range(1, 6) as $intento) {
            $this->postJson('/api/email/verification-notification')->assertSuccessful();
        }

        $this->postJson('/api/email/verification-notification')
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'too_many_requests')
            ->assertJsonPath('error.message', 'Has hecho demasiadas peticiones. Espera un momento y vuelve a intentarlo.')
            ->assertHeader('Retry-After');
    }

    public function test_un_error_inesperado_responde_500_server_error_con_detalles_en_testing(): void
    {
        $this->registerFailingRoute();

        $this->getJson('/api/pruebas/error-inesperado')
            ->assertStatus(500)
            ->assertJsonPath('error.code', 'server_error')
            ->assertJsonPath('error.message', 'Ha ocurrido un error inesperado. Inténtalo de nuevo más tarde.')
            ->assertJsonPath('error.details.exception', RuntimeException::class);
    }

    public function test_un_error_inesperado_no_expone_detalles_fuera_de_local_y_testing(): void
    {
        $this->registerFailingRoute();
        $this->app['env'] = 'production';

        $response = $this->getJson('/api/pruebas/error-inesperado')
            ->assertStatus(500)
            ->assertJsonPath('error.code', 'server_error')
            ->assertJsonMissingPath('error.details');

        $this->assertStringNotContainsString('detalle interno de la prueba', $response->getContent());
        $this->assertStringNotContainsString('trace', $response->getContent());
    }
}
