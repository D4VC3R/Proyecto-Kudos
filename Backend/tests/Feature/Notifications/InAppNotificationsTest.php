<?php

namespace Tests\Feature\Notifications;

use App\Models\ItemComment;
use App\Models\User;
use App\Models\UserList;
use App\Notifications\CommentRepliedNotification;
use App\Notifications\ContentRemovedNotification;
use App\Notifications\InAppNotification;
use App\Notifications\ListLikedNotification;
use App\Notifications\WishlistReleasedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class InAppNotificationsTest extends TestCase
{
    use CreatesItems;
    use RefreshDatabase;

    /**
     * Devuelve una instancia de cada una de las cuatro clases.
     *
     * @return array<int, InAppNotification>
     */
    private function allNotifications(): array
    {
        $item = $this->createItem($this->createCategory('peliculas'));
        $reply = ItemComment::factory()->create(['item_id' => $item->id]);

        return [
            new ContentRemovedNotification('comment', 'Texto', ContentRemovedNotification::ACTION_HIDDEN, 'Spam'),
            new CommentRepliedNotification($reply),
            new ListLikedNotification(UserList::factory()->create(), User::factory()->create()),
            new WishlistReleasedNotification($item),
        ];
    }

    public function test_todas_usan_solo_el_canal_database(): void
    {
        $user = User::factory()->create();

        $notifications = $this->allNotifications();

        foreach ($notifications as $notification) {
            $this->assertSame(['database'], $notification->via($user));
        }

        Notification::fake();

        foreach ($notifications as $notification) {
            $user->notify($notification);

            Notification::assertSentTo(
                $user,
                $notification::class,
                fn ($n, array $channels) => $channels === ['database']
            );
        }
    }

    public function test_content_removed_incluye_el_motivo_y_la_via_de_reclamacion(): void
    {
        $user = User::factory()->create();

        $user->notify(new ContentRemovedNotification(
            'comment',
            'Texto del comentario',
            ContentRemovedNotification::ACTION_HIDDEN,
            'Spam',
            'legal@example.test',
        ));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
            'type' => ContentRemovedNotification::class,
        ]);

        $data = $user->notifications()->first()->data;

        $this->assertSame('content_removed', $data['kind']);
        $this->assertSame('hidden', $data['action']);
        $this->assertSame('Spam', $data['reason']);
        $this->assertSame('legal@example.test', $data['appeal_contact']);
        $this->assertNull($data['url']);
        $this->assertSame('Hemos ocultado tu contenido', $data['title']);
        $this->assertStringContainsString('comentario', $data['message']);
        $this->assertStringContainsString('Spam', $data['message']);
        $this->assertStringContainsString('legal@example.test', $data['message']);
    }

    public function test_content_removed_sin_via_de_reclamacion_no_la_menciona(): void
    {
        $user = User::factory()->create();

        $user->notify(new ContentRemovedNotification(
            'list',
            'Mi lista',
            ContentRemovedNotification::ACTION_DELETED,
            'Contenido ofensivo.',
        ));

        $data = $user->notifications()->first()->data;

        $this->assertSame('Hemos eliminado tu contenido', $data['title']);
        $this->assertStringContainsString('lista', $data['message']);
        $this->assertStringContainsString('eliminado', $data['message']);
        $this->assertStringNotContainsString('reclamar', $data['message']);
        $this->assertNull($data['appeal_contact']);
    }

    public function test_content_removed_de_un_perfil_habla_del_contenido_del_perfil(): void
    {
        $user = User::factory()->create();

        $user->notify(new ContentRemovedNotification(
            'profile',
            'Mi biografía',
            ContentRemovedNotification::ACTION_DELETED,
            'Contenido ofensivo.',
        ));

        $data = $user->notifications()->first()->data;

        $this->assertSame('profile', $data['content_type']);
        $this->assertStringStartsWith('El contenido de tu perfil', $data['message']);
        $this->assertStringContainsString('eliminado', $data['message']);
    }

    public function test_comment_replied_enlaza_a_la_ficha_del_item(): void
    {
        $user = User::factory()->create();
        $item = $this->createItem($this->createCategory('peliculas'));
        $replier = User::factory()->create(['name' => 'Marta']);
        $reply = ItemComment::factory()->create([
            'item_id' => $item->id,
            'user_id' => $replier->id,
            'content' => 'Totalmente de acuerdo contigo',
        ]);

        $user->notify(new CommentRepliedNotification($reply));

        $data = $user->notifications()->first()->data;

        $this->assertSame('comment_replied', $data['kind']);
        $this->assertSame("/peliculas/item/{$item->id}", $data['url']);
        $this->assertSame('Marta', $data['replier_name']);
        $this->assertSame('Totalmente de acuerdo contigo', $data['excerpt']);
        $this->assertSame($item->id, $data['item_id']);
        $this->assertStringContainsString('Marta', $data['message']);
    }

    public function test_list_liked_enlaza_a_la_lista(): void
    {
        $user = User::factory()->create();
        $list = UserList::factory()->create();
        $liker = User::factory()->create(['name' => 'Pablo']);

        $user->notify(new ListLikedNotification($list, $liker));

        $data = $user->notifications()->first()->data;

        $this->assertSame('list_liked', $data['kind']);
        $this->assertSame("/listas/{$list->slug}", $data['url']);
        $this->assertSame('Pablo', $data['liker_name']);
        $this->assertSame($list->id, $data['list_id']);
        $this->assertStringContainsString('Pablo', $data['message']);
    }

    public function test_wishlist_released_enlaza_a_la_ficha(): void
    {
        $user = User::factory()->create();
        $item = $this->createItem($this->createCategory('peliculas'), [
            'name' => 'Dune: Parte tres',
            'released_at' => '2026-12-18',
        ]);

        $user->notify(new WishlistReleasedNotification($item));

        $data = $user->notifications()->first()->data;

        $this->assertSame('wishlist_released', $data['kind']);
        $this->assertSame("/peliculas/item/{$item->id}", $data['url']);
        $this->assertSame('Dune: Parte tres', $data['item_name']);
        $this->assertSame('2026-12-18', $data['released_at']);
        $this->assertStringContainsString('Dune: Parte tres', $data['message']);
    }
}
