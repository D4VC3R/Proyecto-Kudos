<?php

namespace App\Notifications;

use App\Models\Item;
use Illuminate\Notifications\Notification;

/**
 * Base de las notificaciones dentro de la app (F3-05). Solo usan el canal database, nunca el
 * correo (legal/cumplimiento.md §4). toArray() devuelve siempre kind, title, message y url
 * (ruta del frontend o null) más los datos propios de cada tipo. No van por cola: insertar
 * una fila es barato y así no dependen del worker.
 */
abstract class InAppNotification extends Notification
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    abstract public function toArray(object $notifiable): array;

    /**
     * Ruta de la ficha en el frontend (/:categorySlug/item/:itemId), o null sin categoría.
     */
    protected function itemUrl(Item $item): ?string
    {
        $slug = $item->category?->slug;

        return $slug ? "/{$slug}/item/{$item->id}" : null;
    }
}
