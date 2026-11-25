<?php

namespace Tests\Feature\Proposals;

use App\Actions\ReviewProposalAction;
use App\Contracts\Media\MediaStorageInterface;
use App\Models\Category;
use App\Models\Item;
use App\Models\KudosTransaction;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Revisión de propuestas: una segunda revisión concurrente no duplica el ítem y los ficheros se mueven tras el commit.
 */
class ProposalReviewConcurrencyTest extends TestCase
{
    use CreatesItems, RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = $this->createCategory('peliculas');
    }

    private function proposalWithImage(User $creator): Proposal
    {
        $proposal = Proposal::factory()->create([
            'creator_id' => $creator->id,
            'category_id' => $this->category->id,
        ]);
        $proposal->update(['images' => [[
            'variants' => ['original' => "proposals/peliculas/{$proposal->id}/poster.webp"],
            'meta' => [],
            'disk' => 'public',
            'alt' => null,
            'order' => 0,
        ]]]);

        return $proposal;
    }

    public function test_revisar_una_propuesta_que_ya_no_esta_pendiente_da_409_y_no_crea_otro_item(): void
    {
        $creator = User::factory()->create();
        $admin = User::factory()->create();
        $proposal = Proposal::factory()->create([
            'creator_id' => $creator->id,
            'category_id' => $this->category->id,
        ]);

        // Otra revisión se cuela antes del bloqueo: en memoria la propuesta sigue pendiente.
        Proposal::query()->whereKey($proposal->id)->update(['status' => Proposal::STATUS_ACCEPTED]);

        try {
            app(ReviewProposalAction::class)->execute($proposal, $admin, Proposal::STATUS_ACCEPTED, null);
            $this->fail('Debería haberse lanzado un 409.');
        } catch (HttpExceptionInterface $e) {
            $this->assertSame(409, $e->getStatusCode());
            $this->assertSame('Esta propuesta ya está revisada.', $e->getMessage());
        }

        $this->assertSame(0, Item::count());
        $this->assertSame(0, KudosTransaction::where('user_id', $creator->id)->count());
        $this->assertSame(0, $creator->fresh()->creations_accepted);
        $this->assertNull($proposal->fresh()->reviewed_by);
    }

    public function test_los_ficheros_de_la_propuesta_se_mueven_despues_del_commit(): void
    {
        $proposal = $this->proposalWithImage(User::factory()->create());
        $this->actingAsAdmin();

        $levels = [];
        $this->mock(MediaStorageInterface::class, function (MockInterface $mock) use (&$levels) {
            $mock->shouldReceive('move')
                ->once()
                ->andReturnUsing(function ($fromDisk, $from, $toDisk, $to) use (&$levels) {
                    $levels[] = DB::transactionLevel();

                    return $to;
                });
        });
        $base = DB::transactionLevel();

        $this->patchJson("/api/admin/proposals/{$proposal->id}/review", ['status' => 'accepted'])->assertOk();

        $this->assertSame([$base], $levels);
        $item = Item::where('name', $proposal->name)->firstOrFail();
        $this->assertSame("items/peliculas/{$proposal->id}/poster.webp", $item->images[0]['variants']['original']);
    }

    public function test_aceptar_una_propuesta_con_imagenes_las_mueve_a_la_carpeta_del_item(): void
    {
        Storage::fake('public');
        $proposal = $this->proposalWithImage(User::factory()->create());
        Storage::disk('public')->put("proposals/peliculas/{$proposal->id}/poster.webp", 'x');
        $this->actingAsAdmin();

        $this->patchJson("/api/admin/proposals/{$proposal->id}/review", ['status' => 'accepted'])->assertOk();

        Storage::disk('public')->assertExists("items/peliculas/{$proposal->id}/poster.webp");
        Storage::disk('public')->assertMissing("proposals/peliculas/{$proposal->id}/poster.webp");
    }
}
