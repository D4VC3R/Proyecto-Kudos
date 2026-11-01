<?php

namespace Tests\Feature\Reports;

use App\Models\Item;
use App\Models\ItemComment;
use App\Models\Profile;
use App\Models\Report;
use App\Models\User;
use App\Models\UserList;
use App\Services\ModerationAuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\InteractsWithUsers;
use Tests\TestCase;

class AdminReportTest extends TestCase
{
    use CreatesItems, InteractsWithUsers, RefreshDatabase;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        config(['kudos.legal.contact_email' => 'legal@kudos.test']);
        $this->item = $this->createItem($this->createCategory('peliculas'));
    }

    private function commentBy(User $author, string $content = 'Un comentario de prueba'): ItemComment
    {
        return ItemComment::factory()->create(['item_id' => $this->item->id, 'user_id' => $author->id, 'content' => $content]);
    }

    private function reportOf($reportable, ?User $reporter = null, string $reason = 'spam', array $extra = []): Report
    {
        return Report::factory()->create(array_merge([
            'reporter_id' => ($reporter ?? User::factory()->create())->id,
            'reportable_type' => $reportable::class,
            'reportable_id' => $reportable->getKey(),
            'reason' => $reason,
        ], $extra));
    }

    private function resolve(Report $report, array $payload)
    {
        return $this->postJson("/api/admin/reports/{$report->id}/resolve", $payload);
    }

    public function test_un_usuario_normal_no_accede_a_la_cola(): void
    {
        $report = $this->reportOf($this->commentBy(User::factory()->create()));
        $this->seedRolesAndPermissions();
        $this->actingAsUser();

        $this->getJson('/api/admin/reports')->assertForbidden();
        $this->resolve($report, ['action' => 'dismiss'])->assertForbidden();

        $this->assertSame(Report::STATUS_PENDING, $report->fresh()->status);
    }

    public function test_lista_los_reportes_y_filtra_por_estado(): void
    {
        $author = User::factory()->create(['name' => 'Autora']);
        $reporter = User::factory()->create(['name' => 'Denunciante']);
        $list = UserList::factory()->editorial()->create();

        $this->reportOf($this->commentBy($author, 'Texto reportado'), $reporter, 'acoso', ['status' => Report::STATUS_RESOLVED, 'created_at' => now()->subDay()]);
        $this->reportOf($list, $reporter, 'spam', ['status' => Report::STATUS_DISMISSED, 'created_at' => now()->subDays(2)]);
        $pending = $this->reportOf($this->commentBy($author, 'Otro texto'), $reporter, 'odio');

        $this->actingAsAdmin();

        $all = $this->getJson('/api/admin/reports')->assertOk();
        $all->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.pending_count', 1)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.id', $pending->id)
            ->assertJsonPath('data.0.reportable_type', 'comment')
            ->assertJsonPath('data.0.reason_label', 'Discurso de odio')
            ->assertJsonPath('data.0.content.excerpt', 'Otro texto')
            ->assertJsonPath('data.0.content.author.name', 'Autora')
            ->assertJsonPath('data.0.content.url', "/peliculas/item/{$this->item->id}");
        $this->assertSame(['Denunciante'], array_values(array_unique($all->json('data.*.reporter.name'))));

        $this->getJson('/api/admin/reports?status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');

        $this->getJson('/api/admin/reports?reportable_type=list')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reportable_type', 'list')
            ->assertJsonPath('data.0.content.excerpt', $list->title)
            ->assertJsonPath('data.0.content.url', "/listas/{$list->slug}");
    }

    public function test_filtros_no_validos_dan_422(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/admin/reports?status=foo')->assertStatus(422)->assertJsonStructure(['error' => ['details' => ['status']]]);
        $this->getJson('/api/admin/reports?reportable_type=item')->assertStatus(422)->assertJsonStructure(['error' => ['details' => ['reportable_type']]]);
    }

    public function test_flujo_completo_reportar_ocultar_y_notificar_al_autor(): void
    {
        $author = User::factory()->create();
        $reporter = User::factory()->create(['email_verified_at' => now()]);
        $comment = $this->commentBy($author, 'Comprad mis cosas aquí');

        $this->actingAs($reporter, 'sanctum')
            ->postJson('/api/reports', ['reportable_type' => 'comment', 'reportable_id' => $comment->id, 'reason' => 'spam'])
            ->assertCreated();
        $report = Report::query()->firstOrFail();

        $admin = $this->actingAsAdmin();

        $this->resolve($report, ['action' => 'hide', 'note' => 'Es publicidad'])
            ->assertOk()
            ->assertJsonPath('message', 'Reporte resuelto.')
            ->assertJsonPath('data.status', 'resolved')
            ->assertJsonPath('data.resolver.id', $admin->id)
            ->assertJsonPath('meta.content_action', 'hidden')
            ->assertJsonPath('meta.closed_reports', 1);

        $comment->refresh();
        $this->assertTrue($comment->is_hidden);
        $this->assertSame($admin->id, $comment->hidden_by);
        $this->assertStringContainsString('Es publicidad', $comment->hidden_reason);

        $report->refresh();
        $this->assertSame(Report::STATUS_RESOLVED, $report->status);
        $this->assertSame($admin->id, $report->resolved_by);
        $this->assertNotNull($report->resolved_at);
        $this->assertSame('Es publicidad', $report->resolution_note);

        $this->assertCount(1, $author->notifications);
        $data = $author->notifications->first()->data;
        $this->assertSame('content_removed', $data['kind']);
        $this->assertSame('hidden', $data['action']);
        $this->assertSame('comment', $data['content_type']);
        $this->assertSame('legal@kudos.test', $data['appeal_contact']);
        $this->assertStringContainsString('Es publicidad', $data['reason']);
        $this->assertStringContainsString('Spam o publicidad', $data['reason']);
        $this->assertCount(0, $reporter->notifications);

        $this->app['auth']->forgetGuards();
        $this->getJson("/api/items/{$this->item->id}/comments")
            ->assertOk()
            ->assertJsonMissing(['id' => $comment->id]);
    }

    public function test_borrar_un_comentario_lo_borra_y_notifica(): void
    {
        $author = User::factory()->create();
        $comment = $this->commentBy($author);
        $report = $this->reportOf($comment);
        $this->actingAsAdmin();

        $this->resolve($report, ['action' => 'delete', 'note' => 'Incumple las normas'])
            ->assertOk()
            ->assertJsonPath('meta.content_action', 'deleted');

        $this->assertSoftDeleted('item_comments', ['id' => $comment->id]);
        $data = $author->notifications->first()->data;
        $this->assertSame('deleted', $data['action']);
        $this->assertSame('comment', $data['content_type']);
    }

    public function test_ocultar_una_lista_la_hace_privada_y_borrar_la_elimina(): void
    {
        $owner = User::factory()->create();
        $hidden = UserList::factory()->editorial()->create(['user_id' => $owner->id]);
        $deleted = UserList::factory()->editorial()->create(['user_id' => $owner->id]);
        $this->actingAsAdmin();

        $this->getJson("/api/lists/{$hidden->slug}")->assertOk();

        $this->resolve($this->reportOf($hidden), ['action' => 'hide', 'note' => 'No es apta'])
            ->assertOk()
            ->assertJsonPath('meta.content_action', 'hidden');

        $this->assertSame(UserList::VISIBILITY_PRIVATE, $hidden->fresh()->visibility);
        $this->getJson("/api/lists/{$hidden->slug}")->assertNotFound();

        $this->resolve($this->reportOf($deleted), ['action' => 'delete', 'note' => 'Contenido ilegal'])
            ->assertOk()
            ->assertJsonPath('meta.content_action', 'deleted');

        $this->assertSoftDeleted('lists', ['id' => $deleted->id]);
        $this->assertCount(2, $owner->notifications);
        $this->assertSame(['list'], $owner->notifications->pluck('data.content_type')->unique()->values()->all());
    }

    public function test_borrar_un_perfil_vacia_su_contenido_publico(): void
    {
        Storage::fake('public');
        $target = User::factory()->create();
        $path = "avatars/{$target->id}/avatar-x.webp";
        Storage::disk('public')->put($path, 'imagen');
        $target->profile->update([
            'avatar' => $path,
            'biography' => 'Mi biografía ofensiva',
            'city' => 'Madrid',
            'social_links' => ['twitter' => 'https://example.test/yo'],
        ]);
        $report = $this->reportOf($target);
        $this->actingAsAdmin();

        $this->resolve($report, ['action' => 'delete', 'note' => 'Biografía inapropiada'])
            ->assertOk()
            ->assertJsonPath('meta.content_action', 'deleted');

        $profile = Profile::query()->where('user_id', $target->id)->firstOrFail();
        $this->assertNull($profile->biography);
        $this->assertNull($profile->city);
        $this->assertNull($profile->social_links);
        $this->assertNull($profile->avatar);
        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseHas('users', ['id' => $target->id]);

        $notification = $target->notifications->first()->data;
        $this->assertSame('profile', $notification['content_type']);
        $this->assertStringStartsWith('El contenido de tu perfil', $notification['message']);
        $this->assertStringContainsString('Mi biografía ofensiva', $notification['content_excerpt']);
    }

    public function test_un_perfil_no_se_puede_ocultar(): void
    {
        $report = $this->reportOf(User::factory()->create());
        $this->actingAsAdmin();

        $this->resolve($report, ['action' => 'hide', 'note' => 'No'])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['details' => ['action']]]);

        $this->assertSame(Report::STATUS_PENDING, $report->fresh()->status);
    }

    public function test_ocultar_y_borrar_exigen_nota(): void
    {
        $report = $this->reportOf($this->commentBy(User::factory()->create()));
        $this->actingAsAdmin();

        $this->resolve($report, ['action' => 'hide'])->assertStatus(422)->assertJsonStructure(['error' => ['details' => ['note']]]);
        $this->resolve($report, ['action' => 'delete', 'note' => ''])->assertStatus(422)->assertJsonStructure(['error' => ['details' => ['note']]]);
        $this->resolve($report, ['action' => 'dismiss'])->assertOk();
    }

    public function test_descartar_no_toca_el_contenido_ni_notifica(): void
    {
        $author = User::factory()->create();
        $comment = $this->commentBy($author);
        $report = $this->reportOf($comment);
        $admin = $this->actingAsAdmin();

        $this->resolve($report, ['action' => 'dismiss', 'note' => 'Sin infracción'])
            ->assertOk()
            ->assertJsonPath('message', 'Reporte descartado.')
            ->assertJsonPath('data.status', 'dismissed')
            ->assertJsonPath('meta.content_action', 'none');

        $report->refresh();
        $this->assertSame(Report::STATUS_DISMISSED, $report->status);
        $this->assertSame($admin->id, $report->resolved_by);
        $this->assertFalse($comment->fresh()->is_hidden);
        $this->assertNull($comment->fresh()->deleted_at);
        $this->assertCount(0, $author->notifications);
    }

    public function test_un_reporte_ya_resuelto_da_409(): void
    {
        $report = $this->reportOf($this->commentBy(User::factory()->create()), null, 'spam', ['status' => Report::STATUS_RESOLVED]);
        $this->actingAsAdmin();

        $this->resolve($report, ['action' => 'dismiss'])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'conflict');
    }

    public function test_resolver_cierra_los_demas_reportes_pendientes_del_mismo_contenido(): void
    {
        $author = User::factory()->create();
        $comment = $this->commentBy($author);
        $other = $this->commentBy($author, 'Otro comentario');
        $first = $this->reportOf($comment);
        $second = $this->reportOf($comment, null, 'odio');
        $third = $this->reportOf($other);
        $this->actingAsAdmin();

        $this->resolve($first, ['action' => 'hide', 'note' => 'Spam confirmado'])
            ->assertOk()
            ->assertJsonPath('meta.closed_reports', 2);

        $this->assertSame(Report::STATUS_RESOLVED, $first->fresh()->status);
        $this->assertSame(Report::STATUS_RESOLVED, $second->fresh()->status);
        $this->assertSame('Spam confirmado', $second->fresh()->resolution_note);
        $this->assertSame(Report::STATUS_PENDING, $third->fresh()->status);
        $this->assertCount(1, $author->notifications);
    }

    public function test_si_el_contenido_ya_no_existe_se_resuelve_sin_notificar(): void
    {
        $author = User::factory()->create();
        $comment = $this->commentBy($author);
        $report = $this->reportOf($comment);
        $comment->delete();
        $this->actingAsAdmin();

        $this->resolve($report, ['action' => 'delete', 'note' => 'Ya no está'])
            ->assertOk()
            ->assertJsonPath('data.status', 'resolved')
            ->assertJsonPath('data.content.is_deleted', true)
            ->assertJsonPath('meta.content_action', 'none');

        $this->assertCount(0, $author->notifications);
    }

    public function test_sin_correo_legal_la_notificacion_no_incluye_la_via_de_reclamacion(): void
    {
        config(['kudos.legal.contact_email' => null]);
        $author = User::factory()->create();
        $report = $this->reportOf($this->commentBy($author));
        $this->actingAsAdmin();

        $this->resolve($report, ['action' => 'hide', 'note' => 'Spam'])->assertOk();

        $data = $author->notifications->first()->data;
        $this->assertNull($data['appeal_contact']);
        $this->assertStringNotContainsString('reclamar', $data['message']);
    }

    public function test_registra_la_decision_en_el_log_de_moderacion(): void
    {
        $report = $this->reportOf($this->commentBy(User::factory()->create()));
        $this->actingAsAdmin();

        $this->mock(ModerationAuditLogger::class, function (MockInterface $mock) {
            $mock->shouldReceive('logReportResolution')
                ->once()
                ->withArgs(fn ($report, $admin, $action, $note) => $action === 'hide' && $note === 'Spam confirmado');
        });

        $this->resolve($report, ['action' => 'hide', 'note' => 'Spam confirmado'])->assertOk();
    }

    public function test_el_logger_escribe_en_el_canal_moderation(): void
    {
        $admin = User::factory()->create();
        $report = Report::factory()->create();

        Log::shouldReceive('channel')->once()->with('moderation')->andReturnSelf();
        Log::shouldReceive('info')->once()->withArgs(fn ($message, $context) => $message === 'admin.report.resolve' && $context['action'] === 'delete');

        app(ModerationAuditLogger::class)->logReportResolution($report, $admin, 'delete', 'Nota');
    }
}
