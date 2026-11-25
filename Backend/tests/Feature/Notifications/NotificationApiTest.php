<?php

namespace Tests\Feature\Notifications;

use App\Models\Item;
use App\Models\User;
use App\Notifications\WishlistReleasedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesItems;
use Tests\Concerns\InteractsWithUsers;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use CreatesItems;
    use InteractsWithUsers;
    use RefreshDatabase;

    private ?Item $item = null;

    /**
     * Crea una notificación de prueba para el usuario (inserta una fila real en notifications).
     */
    private function notifyUser(User $user): string
    {
        $this->item ??= $this->createItem($this->createCategory('peliculas'));

        $before = $user->notifications()->pluck('id');
        $user->notify(new WishlistReleasedNotification($this->item));

        return $user->notifications()->pluck('id')->diff($before)->first();
    }

    public function test_sin_sesion_los_endpoints_dan_401(): void
    {
        $this->getJson('/api/notifications')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'unauthenticated');

        $this->postJson('/api/notifications/read-all')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'unauthenticated');

        $this->postJson('/api/notifications/'.Str::uuid().'/read')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_sin_email_verificado_da_403(): void
    {
        $this->actingAsUser(['email_verified_at' => null]);

        $this->getJson('/api/notifications')->assertStatus(403);
        $this->postJson('/api/notifications/read-all')->assertStatus(403);
        $this->postJson('/api/notifications/'.Str::uuid().'/read')->assertStatus(403);
    }

    public function test_lista_las_no_leidas_primero_y_despues_por_fecha(): void
    {
        $user = $this->actingAsUser();

        $this->travelTo(now()->subHours(4));
        $a = $this->notifyUser($user); // antigua, no leída
        $this->travelTo(now()->addHour());
        $b = $this->notifyUser($user); // reciente, leída
        $this->travelTo(now()->addHour());
        $c = $this->notifyUser($user); // reciente, no leída
        $this->travelTo(now()->subHours(3));
        $d = $this->notifyUser($user); // antigua, leída
        $this->travelBack();

        $user->notifications()->whereIn('id', [$b, $d])->update(['read_at' => now()]);

        $response = $this->getJson('/api/notifications')->assertOk();

        $this->assertSame([$c, $a, $b, $d], collect($response->json('data'))->pluck('id')->all());
        $response->assertJsonPath('meta.unread_count', 2);
        $response->assertJsonPath('meta.total', 4);
    }

    public function test_pagina_con_per_page(): void
    {
        $user = $this->actingAsUser();
        foreach (range(1, 3) as $i) {
            $this->notifyUser($user);
        }

        $this->getJson('/api/notifications?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.last_page', 2);

        $this->getJson('/api/notifications?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_per_page_fuera_de_rango_da_422(): void
    {
        $this->actingAsUser();

        $this->getJson('/api/notifications?per_page=0')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');

        $this->getJson('/api/notifications?per_page=51')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    }

    public function test_solo_devuelve_las_notificaciones_del_usuario(): void
    {
        $user = $this->actingAsUser();
        $other = User::factory()->create();

        $mine = $this->notifyUser($user);
        $theirs = $this->notifyUser($other);

        $response = $this->getJson('/api/notifications')->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($mine, $ids);
        $this->assertNotContains($theirs, $ids);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('meta.unread_count', 1);
    }

    public function test_estructura_de_cada_notificacion(): void
    {
        $user = $this->actingAsUser();
        $this->notifyUser($user);

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'kind',
                    'data' => ['kind', 'title', 'message', 'url'],
                    'is_read',
                    'read_at',
                    'created_at',
                ]],
                'meta' => ['current_page', 'last_page', 'per_page', 'total', 'unread_count'],
            ])
            ->assertJsonPath('data.0.kind', 'wishlist_released')
            ->assertJsonPath('data.0.is_read', false);
    }

    public function test_marca_una_notificacion_como_leida(): void
    {
        $user = $this->actingAsUser();
        $id = $this->notifyUser($user);
        $this->notifyUser($user);

        $this->postJson("/api/notifications/{$id}/read")
            ->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.is_read', true)
            ->assertJsonPath('meta.unread_count', 1);

        $this->assertNotNull($user->notifications()->whereKey($id)->first()->read_at);
    }

    public function test_marcar_como_leida_dos_veces_no_cambia_la_fecha(): void
    {
        $user = $this->actingAsUser();
        $id = $this->notifyUser($user);

        $this->travelTo(now()->addMinute());
        $first = $this->postJson("/api/notifications/{$id}/read")->assertOk()->json('data.read_at');

        $this->travelTo(now()->addHour());
        $second = $this->postJson("/api/notifications/{$id}/read")->assertOk()->json('data.read_at');

        $this->assertNotNull($first);
        $this->assertSame($first, $second);
    }

    public function test_no_puede_marcar_la_notificacion_de_otro_usuario(): void
    {
        $this->actingAsUser();
        $other = User::factory()->create();
        $id = $this->notifyUser($other);

        $this->postJson("/api/notifications/{$id}/read")
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'not_found');

        $this->assertNull($other->notifications()->whereKey($id)->first()->read_at);
    }

    public function test_id_inexistente_o_no_uuid_da_404(): void
    {
        $this->actingAsUser();

        // Un UUID que no existe es un 404 de modelo (not_found); un id que no es UUID no casa con la ruta (route_not_found).
        $this->postJson('/api/notifications/'.Str::uuid().'/read')
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'not_found');

        $this->postJson('/api/notifications/abc/read')
            ->assertStatus(404)
            ->assertJsonPath('error.code', 'route_not_found');
    }

    public function test_marca_todas_como_leidas_solo_las_del_usuario(): void
    {
        $user = $this->actingAsUser();
        $other = User::factory()->create();

        foreach (range(1, 3) as $i) {
            $this->notifyUser($user);
        }
        $this->notifyUser($other);

        $this->postJson('/api/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('meta.updated', 3)
            ->assertJsonPath('meta.unread_count', 0);

        $this->assertSame(0, $user->unreadNotifications()->count());
        $this->assertSame(1, $other->unreadNotifications()->count());
    }
}
