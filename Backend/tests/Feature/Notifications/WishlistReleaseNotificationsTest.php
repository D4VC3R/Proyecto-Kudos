<?php

namespace Tests\Feature\Notifications;

use App\Console\Commands\NotifyWishlistReleasesCommand;
use App\Jobs\NotifyWishlistReleasesJob;
use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use App\Notifications\WishlistReleasedNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class WishlistReleaseNotificationsTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    private function wantFor(User $user, Item $item): Vote
    {
        return Vote::factory()->byUser($user)->forItem($item)->create(['type' => Vote::TYPE_WANT, 'score' => null]);
    }

    private function wishlistNotificationsFor(User $user)
    {
        return $user->notifications()->where('type', WishlistReleasedNotification::class)->get();
    }

    private function runCommand(): void
    {
        $this->artisan('wishlist:notify-releases')->assertSuccessful();
    }

    public function test_el_aviso_se_envia_una_sola_vez(): void
    {
        $user = User::factory()->create();
        $item = $this->createItem($this->createCategory(), ['released_at' => '2026-12-18']);
        $this->wantFor($user, $item);

        $this->travelTo(Carbon::parse('2026-12-17 06:00:00'));
        $this->runCommand();
        $this->assertCount(0, $this->wishlistNotificationsFor($user));

        $this->travelTo(Carbon::parse('2026-12-18 06:00:00'));
        $this->runCommand();
        $notifications = $this->wishlistNotificationsFor($user);
        $this->assertCount(1, $notifications);
        $data = $notifications->first()->data;
        $this->assertSame('wishlist_released', $data['kind']);
        $this->assertSame($item->id, $data['item_id']);
        $this->assertSame('2026-12-18', $data['released_at']);

        $this->runCommand();
        $this->assertCount(1, $this->wishlistNotificationsFor($user));

        $this->travelTo(Carbon::parse('2026-12-19 06:00:00'));
        $this->runCommand();
        $this->assertCount(1, $this->wishlistNotificationsFor($user));
    }

    public function test_no_repite_el_aviso_si_se_pierde_la_marca_de_la_cache(): void
    {
        $user = User::factory()->create();
        $item = $this->createItem($this->createCategory(), ['released_at' => '2026-12-18']);
        $this->wantFor($user, $item);

        $this->travelTo(Carbon::parse('2026-12-18 06:00:00'));
        $this->runCommand();
        $this->assertCount(1, $this->wishlistNotificationsFor($user));

        Cache::forget(NotifyWishlistReleasesCommand::CACHE_KEY);
        $this->runCommand();

        $this->assertCount(1, $this->wishlistNotificationsFor($user));
    }

    public function test_solo_avisa_a_los_usuarios_que_lo_tienen_en_want(): void
    {
        $item = $this->createItem($this->createCategory(), ['released_at' => '2026-12-18']);
        $want = User::factory()->create();
        $voted = User::factory()->create();
        $notInterested = User::factory()->create();
        $skipped = User::factory()->create();

        $this->wantFor($want, $item);
        Vote::factory()->byUser($voted)->forItem($item)->rated(8)->create();
        Vote::factory()->byUser($notInterested)->forItem($item)->create(['type' => Vote::TYPE_NOT_INTERESTED, 'score' => null]);
        Vote::factory()->byUser($skipped)->forItem($item)->create(['type' => Vote::TYPE_SKIP, 'score' => null]);

        $this->travelTo(Carbon::parse('2026-12-18 06:00:00'));
        $this->runCommand();

        $this->assertCount(1, $this->wishlistNotificationsFor($want));
        $this->assertCount(0, $this->wishlistNotificationsFor($voted));
        $this->assertCount(0, $this->wishlistNotificationsFor($notInterested));
        $this->assertCount(0, $this->wishlistNotificationsFor($skipped));
    }

    public function test_no_avisa_de_items_inactivos_borrados_sin_fecha_o_de_categoria_inactiva(): void
    {
        $user = User::factory()->create();
        $category = $this->createCategory();

        $inactive = $this->createItem($category, ['released_at' => '2026-12-18', 'status' => Item::STATUS_INACTIVE]);
        $deleted = $this->createItem($category, ['released_at' => '2026-12-18']);
        $withoutDate = $this->createItem($category, ['released_at' => null]);
        $inactiveCategory = $this->createCategory('series', 'Series');
        $inactiveCategory->update(['is_active' => false]);
        $ofInactiveCategory = $this->createItem($inactiveCategory, ['released_at' => '2026-12-18']);

        foreach ([$inactive, $deleted, $withoutDate, $ofInactiveCategory] as $item) {
            $this->wantFor($user, $item);
        }
        $deleted->delete();

        $this->travelTo(Carbon::parse('2026-12-18 06:00:00'));
        $this->runCommand();

        $this->assertCount(0, $this->wishlistNotificationsFor($user));
    }

    public function test_no_avisa_de_estrenos_futuros(): void
    {
        $user = User::factory()->create();
        $item = $this->createItem($this->createCategory(), ['released_at' => '2026-12-19']);
        $this->wantFor($user, $item);

        $this->travelTo(Carbon::parse('2026-12-18 06:00:00'));
        $this->runCommand();
        $this->assertCount(0, $this->wishlistNotificationsFor($user));

        $this->travelTo(Carbon::parse('2026-12-19 06:00:00'));
        $this->runCommand();
        $this->assertCount(1, $this->wishlistNotificationsFor($user));
    }

    public function test_avisa_de_los_estrenos_de_los_dias_en_que_no_se_ejecuto(): void
    {
        $user = User::factory()->create();
        $category = $this->createCategory();
        $first = $this->createItem($category, ['released_at' => '2026-12-16']);
        $second = $this->createItem($category, ['released_at' => '2026-12-18']);
        $this->wantFor($user, $first);
        $this->wantFor($user, $second);

        $this->travelTo(Carbon::parse('2026-12-15 06:00:00'));
        $this->runCommand();
        $this->assertCount(0, $this->wishlistNotificationsFor($user));

        $this->travelTo(Carbon::parse('2026-12-18 06:00:00'));
        $this->runCommand();

        $this->assertCount(2, $this->wishlistNotificationsFor($user));
    }

    public function test_sin_marca_revisa_los_ultimos_dias_configurados(): void
    {
        config(['kudos.wishlist.release_lookback_days' => 7]);

        $user = User::factory()->create();
        $category = $this->createCategory();
        $inside = $this->createItem($category, ['released_at' => '2026-12-14']);
        $outside = $this->createItem($category, ['released_at' => '2026-12-10']);
        $this->wantFor($user, $inside);
        $this->wantFor($user, $outside);

        $this->travelTo(Carbon::parse('2026-12-18 06:00:00'));
        $this->runCommand();

        $notifications = $this->wishlistNotificationsFor($user);
        $this->assertCount(1, $notifications);
        $this->assertSame($inside->id, $notifications->first()->data['item_id']);
    }

    public function test_guarda_la_marca_con_la_fecha_de_hoy(): void
    {
        $this->travelTo(Carbon::parse('2026-12-18 06:00:00'));
        $this->runCommand();

        $this->assertSame('2026-12-18', Cache::get(NotifyWishlistReleasesCommand::CACHE_KEY));
    }

    public function test_el_comando_solo_encola_en_la_cola_default(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = $this->createCategory();
        $wanted = $this->createItem($category, ['released_at' => '2026-12-18']);
        $this->createItem($category, ['released_at' => '2026-12-18']);
        $this->wantFor($user, $wanted);

        $this->travelTo(Carbon::parse('2026-12-18 06:00:00'));
        $this->runCommand();

        Queue::assertPushedOn('default', NotifyWishlistReleasesJob::class);
        Queue::assertPushed(NotifyWishlistReleasesJob::class, 1);
        Queue::assertPushed(NotifyWishlistReleasesJob::class, fn ($job) => $job->itemIds === [$wanted->id]);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_el_aviso_esta_programado_a_diario_a_las_seis(): void
    {
        // schedule:list carga routes/console.php, donde se registra la programación.
        Artisan::call('schedule:list');

        $event = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains((string) $e->command, 'wishlist:notify-releases'));

        $this->assertNotNull($event);
        $this->assertSame('0 6 * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }
}
