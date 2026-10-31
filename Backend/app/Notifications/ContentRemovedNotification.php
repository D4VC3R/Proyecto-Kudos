<?php

namespace App\Notifications;

use Illuminate\Support\Str;

/**
 * Aviso de que un contenido del usuario se ha ocultado o eliminado por moderación (la dispara la F3-04).
 */
class ContentRemovedNotification extends InAppNotification
{
    public const ACTION_HIDDEN = 'hidden';

    public const ACTION_DELETED = 'deleted';

    /**
     * @param  string  $contentType  'comment' | 'list' | 'profile'
     * @param  string  $action  self::ACTION_HIDDEN | self::ACTION_DELETED
     * @param  string|null  $appealContact  vía de reclamación (correo del aviso legal); la pasa la F3-04
     */
    public function __construct(
        public readonly string $contentType,
        public readonly string $contentExcerpt,
        public readonly string $action,
        public readonly string $reason,
        public readonly ?string $appealContact = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $hidden = $this->action === self::ACTION_HIDDEN;
        $excerpt = Str::limit($this->contentExcerpt, 100);

        $label = match ($this->contentType) {
            'comment' => 'comentario',
            'list' => 'lista',
            'profile' => 'perfil',
            default => 'contenido',
        };

        $message = sprintf(
            'Tu %s «%s» se ha %s. Motivo: %s.',
            $label,
            $excerpt,
            $hidden ? 'ocultado' : 'eliminado',
            rtrim($this->reason, '. '),
        );

        if ($this->appealContact) {
            $message .= " Si no estás de acuerdo, puedes reclamar escribiendo a {$this->appealContact}.";
        }

        return [
            'kind' => 'content_removed',
            'title' => $hidden ? 'Hemos ocultado tu contenido' : 'Hemos eliminado tu contenido',
            'message' => $message,
            'url' => null,
            'content_type' => $this->contentType,
            'content_excerpt' => $excerpt,
            'action' => $this->action,
            'reason' => $this->reason,
            'appeal_contact' => $this->appealContact,
        ];
    }
}
