<?php

namespace App\Notifications;

use App\Models\Item;

/**
 * Aviso de que un ítem de la wishlist del usuario ya está disponible (lo dispara la F3-06).
 */
class WishlistReleasedNotification extends InAppNotification
{
    public function __construct(public readonly Item $item) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->item->loadMissing('category:id,slug');

        return [
            'kind' => 'wishlist_released',
            'title' => 'Ya está disponible',
            'message' => "«{$this->item->name}», de tu wishlist, ya está disponible.",
            'url' => $this->itemUrl($this->item),
            'item_id' => $this->item->id,
            'item_name' => $this->item->name,
            'released_at' => $this->item->released_at?->toDateString(),
        ];
    }
}
