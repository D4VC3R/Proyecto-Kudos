<?php

namespace App\Notifications;

use App\Models\ItemComment;
use Illuminate\Support\Str;

/**
 * Aviso de que alguien ha respondido a un comentario del usuario (la dispara la F3-02).
 */
class CommentRepliedNotification extends InAppNotification
{
    public function __construct(public readonly ItemComment $reply) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->reply->loadMissing(['user:id,name', 'item:id,name,category_id', 'item.category:id,slug']);

        $item = $this->reply->item;
        // Si el autor es null (la F3-08 anonimiza) no hay nombre que mostrar.
        $replierName = $this->reply->user?->name ?? 'Alguien';
        $excerpt = Str::limit((string) $this->reply->content, 100);

        return [
            'kind' => 'comment_replied',
            'title' => 'Han respondido a tu comentario',
            'message' => "{$replierName} ha respondido a tu comentario en «{$item?->name}»: «{$excerpt}»",
            'url' => $item ? $this->itemUrl($item) : null,
            'comment_id' => $this->reply->id,
            'item_id' => $item?->id,
            'item_name' => $item?->name,
            'replier_name' => $replierName,
            'excerpt' => $excerpt,
        ];
    }
}
