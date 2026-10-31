<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\UserList;

/**
 * Aviso de que a alguien le gusta una lista del usuario (la dispara la F3-01, que se encarga
 * del límite de 1 al día por lista).
 */
class ListLikedNotification extends InAppNotification
{
    public function __construct(public readonly UserList $list, public readonly User $liker) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'list_liked',
            'title' => 'A alguien le gusta tu lista',
            'message' => "A {$this->liker->name} le gusta tu lista «{$this->list->title}».",
            'url' => "/listas/{$this->list->slug}",
            'list_id' => $this->list->id,
            'list_slug' => $this->list->slug,
            'list_title' => $this->list->title,
            'liker_id' => $this->liker->id,
            'liker_name' => $this->liker->name,
        ];
    }
}
