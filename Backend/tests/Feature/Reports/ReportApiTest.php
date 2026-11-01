<?php

namespace Tests\Feature\Reports;

use App\Models\ItemComment;
use App\Models\Report;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\InteractsWithUsers;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    use CreatesItems, InteractsWithUsers, RefreshDatabase;

    /**
     * POST /api/reports con valores válidos por defecto (un reporte de spam).
     */
    private function report(array $overrides = []): TestResponse
    {
        return $this->postJson('/api/reports', array_merge([
            'reportable_type' => 'comment',
            'reportable_id' => null,
            'reason' => 'spam',
        ], $overrides));
    }

    private function commentOf(User $author): ItemComment
    {
        $item = $this->createItem($this->createCategory('peliculas'));

        return ItemComment::factory()->create(['item_id' => $item->id, 'user_id' => $author->id]);
    }

    public function test_sin_sesion_da_401(): void
    {
        $comment = $this->commentOf(User::factory()->create());

        $this->report(['reportable_id' => $comment->id])
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_sin_email_verificado_da_403(): void
    {
        $comment = $this->commentOf(User::factory()->create());
        $this->actingAsUser(['email_verified_at' => null]);

        $this->report(['reportable_id' => $comment->id])->assertForbidden();

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_reporta_un_comentario(): void
    {
        $comment = $this->commentOf(User::factory()->create());
        $reporter = $this->actingAsUser();

        $this->report(['reportable_id' => $comment->id, 'reason' => 'acoso', 'details' => 'Insulta a otro usuario'])
            ->assertCreated()
            ->assertJsonPath('data.reportable_type', 'comment')
            ->assertJsonPath('data.reportable_id', $comment->id)
            ->assertJsonPath('data.reason', 'acoso')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $reporter->id,
            'reportable_type' => ItemComment::class,
            'reportable_id' => $comment->id,
            'reason' => 'acoso',
            'status' => 'pending',
        ]);
    }

    public function test_reporta_una_lista_publica_y_un_perfil(): void
    {
        $list = UserList::factory()->editorial()->create();
        $other = User::factory()->create();
        $this->actingAsUser();

        $this->report(['reportable_type' => 'list', 'reportable_id' => $list->id])
            ->assertCreated()
            ->assertJsonPath('data.reportable_type', 'list');

        $this->report(['reportable_type' => 'user', 'reportable_id' => $other->id, 'reason' => 'ilegal'])
            ->assertCreated()
            ->assertJsonPath('data.reportable_type', 'user');

        $this->assertDatabaseHas('reports', ['reportable_type' => UserList::class, 'reportable_id' => $list->id]);
        $this->assertDatabaseHas('reports', ['reportable_type' => User::class, 'reportable_id' => $other->id]);
    }

    public function test_no_puede_reportar_una_lista_privada(): void
    {
        $list = UserList::factory()->create(['visibility' => UserList::VISIBILITY_PRIVATE]);
        $this->actingAsUser();

        $this->report(['reportable_type' => 'list', 'reportable_id' => $list->id])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['reportable_id']]]);

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_no_puede_reportar_contenido_inexistente_o_borrado(): void
    {
        $comment = $this->commentOf(User::factory()->create());
        $comment->delete();
        $this->actingAsUser();

        $this->report(['reportable_id' => (string) Str::uuid()])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['reportable_id']]]);

        $this->report(['reportable_id' => $comment->id])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['reportable_id']]]);
    }

    public function test_no_puede_reportar_su_propio_contenido(): void
    {
        $me = $this->actingAsUser();
        $comment = $this->commentOf($me);

        $this->report(['reportable_id' => $comment->id])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['reportable_id']]]);

        $this->report(['reportable_type' => 'user', 'reportable_id' => $me->id])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['reportable_id']]]);

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_valida_tipo_motivo_y_detalles(): void
    {
        $comment = $this->commentOf(User::factory()->create());
        $this->actingAsUser();

        $this->report(['reportable_type' => 'item', 'reportable_id' => $comment->id])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['reportable_type']]]);

        $this->report(['reportable_id' => $comment->id, 'reason' => 'feo'])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['reason']]]);

        $this->report(['reportable_id' => $comment->id, 'reason' => 'otro'])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['details']]]);

        $this->report(['reportable_id' => $comment->id, 'details' => str_repeat('a', 1001)])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['details']]]);

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_no_puede_reportar_dos_veces_lo_mismo_mientras_esta_pendiente(): void
    {
        $comment = $this->commentOf(User::factory()->create());
        $this->actingAsUser();

        $this->report(['reportable_id' => $comment->id])->assertCreated();

        $this->report(['reportable_id' => $comment->id])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'conflict');

        $this->assertDatabaseCount('reports', 1);

        Report::query()->update(['status' => Report::STATUS_RESOLVED]);

        $this->report(['reportable_id' => $comment->id])->assertCreated();
    }

    public function test_otro_usuario_puede_reportar_el_mismo_contenido(): void
    {
        $comment = $this->commentOf(User::factory()->create());

        $this->actingAsUser();
        $this->report(['reportable_id' => $comment->id])->assertCreated();

        $this->actingAsUser();
        $this->report(['reportable_id' => $comment->id])->assertCreated();

        $this->assertDatabaseCount('reports', 2);
    }

    public function test_limite_de_10_reportes_por_hora(): void
    {
        $item = $this->createItem($this->createCategory('peliculas'));
        $comments = ItemComment::factory()->count(11)->create(['item_id' => $item->id]);
        $this->actingAsUser();

        foreach ($comments->take(10) as $comment) {
            $this->report(['reportable_id' => $comment->id])->assertCreated();
        }

        $this->report(['reportable_id' => $comments->last()->id])
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'too_many_requests');

        $this->assertDatabaseCount('reports', 10);
    }
}
