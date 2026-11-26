<?php

namespace App\Jobs;

use App\Models\Item;
use App\Models\User;
use App\Models\Vote;
use App\Notifications\WishlistReleasedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

/**
 * Avisa del estreno de un bloque de ítems a los usuarios que los tienen en su wishlist (votos 'want').
 * Lo encola wishlist:notify-releases en la cola 'default'. Es idempotente: no envía el aviso a quien ya
 * tiene una WishlistReleasedNotification de ese ítem, así que repetirlo (reintentos, marca perdida) no duplica.
 */
class NotifyWishlistReleasesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    // Usuarios por bloque al enviar (cada aviso es un INSERT síncrono en notifications).
    public const USER_CHUNK = 500;

    /**
     * @param  array<int, string>  $itemIds  IDs de los ítems del bloque.
     */
    public function __construct(public array $itemIds)
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        // Se revalida entre el encolado y la ejecución: activo, sin borrar, con fecha y de categoría activa.
        $items = Item::query()
            ->whereKey($this->itemIds)
            ->visible()
            ->whereNotNull('released_at')
            ->with('category:id,slug')
            ->get();

        foreach ($items as $item) {
            $this->notifyItem($item);
        }
    }

    private function notifyItem(Item $item): void
    {
        User::query()
            ->whereHas('votes', fn ($q) => $q->where('item_id', $item->id)->where('type', Vote::TYPE_WANT))
            ->whereDoesntHave('notifications', fn ($q) => $q
                ->where('type', WishlistReleasedNotification::class)
                // data es text (tabla estándar de Laravel): se castea a jsonb para leer item_id.
                ->whereRaw("(data::jsonb ->> 'item_id') = ?", [$item->id]))
            ->chunkById(self::USER_CHUNK, function ($users) use ($item) {
                Notification::send($users, new WishlistReleasedNotification($item));
            });
    }
}
