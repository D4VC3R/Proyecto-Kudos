<?php

namespace Tests\Feature\Proposals;

use App\Models\Category;
use App\Models\Proposal;
use App\Services\Media\RemoteImageDownloader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Crear una propuesta no falla si el procesado de imágenes falla, pero la respuesta lo indica.
 */
class ProposalStoreImagesTest extends TestCase
{
    use CreatesItems, RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = $this->createCategory();
    }

    private function validPayload(array $extra = []): array
    {
        return array_merge([
            'name' => 'Película propuesta',
            'description' => 'Una descripción de al menos veinte caracteres.',
            'category_id' => $this->category->id,
        ], $extra);
    }

    public function test_store_con_el_procesado_de_imagenes_fallando_responde_201_con_images_failed(): void
    {
        Exceptions::fake();
        // El job falla al resolver sus dependencias, así que no se hace ninguna petición HTTP.
        $this->app->bind(RemoteImageDownloader::class, function () {
            throw new \RuntimeException('Fallo simulado al procesar las imágenes.');
        });
        $this->actingAsUser();

        $this->postJson('/api/proposals', $this->validPayload(['image_path' => 'https://image.tmdb.org/t/p/w780/a.jpg']))
            ->assertCreated()
            ->assertJsonPath('meta.images_failed', true);

        $proposal = Proposal::where('name', 'Película propuesta')->firstOrFail();
        $this->assertSame([], $proposal->images);
        Exceptions::assertReported(\RuntimeException::class);
    }

    public function test_store_sin_imagenes_no_anade_images_failed(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/proposals', $this->validPayload())
            ->assertCreated()
            ->assertJsonMissingPath('meta.images_failed');
    }
}
