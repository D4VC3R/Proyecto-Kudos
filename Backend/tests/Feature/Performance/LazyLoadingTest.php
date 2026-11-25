<?php

namespace Tests\Feature\Performance;

use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * La carga perezosa está prohibida fuera de producción, así que un N+1 nuevo hace fallar los tests.
 */
class LazyLoadingTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    public function test_la_carga_perezosa_esta_prohibida_fuera_de_produccion(): void
    {
        $this->assertTrue(Model::preventsLazyLoading());
    }

    public function test_mis_propuestas_traen_la_categoria_y_el_revisor_sin_carga_perezosa(): void
    {
        $category = $this->createCategory('peliculas', 'Películas');
        $user = $this->actingAsUser();
        $admin = User::factory()->create();

        Proposal::factory()->create(['creator_id' => $user->id, 'category_id' => $category->id]);
        Proposal::factory()->accepted($admin)->create(['creator_id' => $user->id, 'category_id' => $category->id]);

        $response = $this->getJson('/api/proposals/my-proposals');

        $response->assertOk()->assertJsonCount(2, 'data');
        $this->assertSame(['peliculas', 'peliculas'], collect($response->json('data'))->pluck('categorySlug')->all());
        $this->assertContains($admin->name, collect($response->json('data'))->pluck('reviewer.name')->all());
    }
}
