<?php

namespace Tests\Feature\Items;

use App\Models\Item;
use App\Models\ItemComment;
use App\Models\Report;
use App\Models\User;
use App\Services\ModerationAuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

/**
 * Moderación de comentarios desde el panel: aviso al autor (exposición de motivos de la DSA) y registro de la decisión.
 */
class CommentModerationNoticeTest extends TestCase
{
    use CreatesItems, RefreshDatabase;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        config(['kudos.legal.contact_email' => 'legal@kudos.test']);
        $this->item = $this->createItem($this->createCategory());
    }

    private function commentBy(?User $author, array $attrs = []): ItemComment
    {
        return ItemComment::factory()->create(array_merge([
            'item_id' => $this->item->id,
            'user_id' => $author?->id,
            'content' => 'Comentario original',
        ], $attrs));
    }

    public function test_ocultar_desde_el_panel_avisa_al_autor_con_el_motivo(): void
    {
        $author = User::factory()->create();
        $comment = $this->commentBy($author);
        $this->actingAsAdmin();

        $this->patchJson("/api/admin/comments/{$comment->id}/hide", ['reason' => 'Spam'])->assertOk();

        $this->assertCount(1, $author->notifications);
        $data = $author->notifications->first()->data;
        $this->assertSame('content_removed', $data['kind']);
        $this->assertSame('hidden', $data['action']);
        $this->assertSame('comment', $data['content_type']);
        $this->assertSame('Spam', $data['reason']);
        $this->assertSame('legal@kudos.test', $data['appeal_contact']);
    }

    public function test_ocultar_sin_motivo_avisa_con_el_motivo_generico(): void
    {
        $author = User::factory()->create();
        $comment = $this->commentBy($author);
        $this->actingAsAdmin();

        $this->patchJson("/api/admin/comments/{$comment->id}/hide")->assertOk();

        $this->assertCount(1, $author->notifications);
        $data = $author->notifications->first()->data;
        $this->assertSame(Report::REASON_LABELS['otro'], $data['reason']);
        $this->assertStringNotContainsString('Motivo: .', $data['message']);
    }

    public function test_borrar_como_admin_avisa_al_autor(): void
    {
        $author = User::factory()->create();
        $comment = $this->commentBy($author);
        $this->actingAsAdmin();

        $this->deleteJson("/api/comments/{$comment->id}")->assertOk();

        $this->assertSoftDeleted($comment);
        $this->assertCount(1, $author->notifications);
        $data = $author->notifications->first()->data;
        $this->assertSame('deleted', $data['action']);
        $this->assertSame('comment', $data['content_type']);
    }

    public function test_restaurar_no_avisa_al_autor(): void
    {
        $author = User::factory()->create();
        $comment = $this->commentBy($author, ['is_hidden' => true]);
        $this->actingAsAdmin();

        $this->patchJson("/api/admin/comments/{$comment->id}/unhide")->assertOk();

        $this->assertCount(0, $author->notifications);
    }

    public function test_el_admin_que_modera_su_propio_comentario_no_recibe_aviso(): void
    {
        $admin = $this->actingAsAdmin();
        $comment = $this->commentBy($admin);

        $this->patchJson("/api/admin/comments/{$comment->id}/hide", ['reason' => 'Spam'])->assertOk();

        $this->assertCount(0, $admin->notifications);
    }

    public function test_un_comentario_anonimizado_se_oculta_sin_aviso(): void
    {
        $comment = $this->commentBy(null);
        $this->actingAsAdmin();

        $this->patchJson("/api/admin/comments/{$comment->id}/hide", ['reason' => 'Spam'])->assertOk();

        $this->assertTrue($comment->fresh()->is_hidden);
        $this->assertSame(0, DB::table('notifications')->count());
    }

    public function test_ocultar_queda_en_el_registro_de_moderacion(): void
    {
        $comment = $this->commentBy(User::factory()->create());
        $this->actingAsAdmin();

        $this->mock(ModerationAuditLogger::class, function (MockInterface $mock) use ($comment) {
            $mock->shouldReceive('logCommentModeration')
                ->once()
                ->withArgs(fn ($c, $admin, $action, $reason) => $c->id === $comment->id && $action === 'hide' && $reason === 'Spam');
        });

        $this->patchJson("/api/admin/comments/{$comment->id}/hide", ['reason' => 'Spam'])->assertOk();
    }

    public function test_restaurar_queda_en_el_registro_de_moderacion(): void
    {
        $comment = $this->commentBy(User::factory()->create(), ['is_hidden' => true]);
        $this->actingAsAdmin();

        $this->mock(ModerationAuditLogger::class, function (MockInterface $mock) use ($comment) {
            $mock->shouldReceive('logCommentModeration')
                ->once()
                ->withArgs(fn ($c, $admin, $action, $reason) => $c->id === $comment->id && $action === 'unhide' && $reason === null);
        });

        $this->patchJson("/api/admin/comments/{$comment->id}/unhide")->assertOk();
    }

    public function test_borrar_como_admin_queda_en_el_registro_de_moderacion(): void
    {
        $comment = $this->commentBy(User::factory()->create());
        $this->actingAsAdmin();

        $this->mock(ModerationAuditLogger::class, function (MockInterface $mock) use ($comment) {
            $mock->shouldReceive('logCommentModeration')
                ->once()
                ->withArgs(fn ($c, $admin, $action, $reason) => $c->id === $comment->id && $action === 'delete' && $reason === null);
        });

        $this->deleteJson("/api/comments/{$comment->id}")->assertOk();
    }

    public function test_el_logger_de_comentarios_escribe_en_el_canal_moderation(): void
    {
        $admin = User::factory()->create();
        $comment = $this->commentBy(User::factory()->create());

        Log::shouldReceive('channel')->once()->with('moderation')->andReturnSelf();
        Log::shouldReceive('info')->once()->withArgs(
            fn ($message, $context) => $message === 'admin.comment.moderation'
                && $context['action'] === 'hide'
                && $context['comment_id'] === $comment->id
        );

        app(ModerationAuditLogger::class)->logCommentModeration($comment, $admin, 'hide', 'Spam');
    }
}
