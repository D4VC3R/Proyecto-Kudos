<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_endpoint_de_salud_responde(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_el_listado_de_categorias_devuelve_json_valido(): void
    {
        Category::factory()->create();

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'slug']]])
            ->assertJsonCount(1, 'data');
    }

    public function test_los_helpers_autentican_usuarios_y_administradores(): void
    {
        $user = $this->actingAsUser();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertFalse($user->is_banned);
        $this->assertFalse($user->hasRole('admin'));

        $admin = $this->actingAsAdmin();
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertAuthenticatedAs($admin, 'sanctum');
    }
}
