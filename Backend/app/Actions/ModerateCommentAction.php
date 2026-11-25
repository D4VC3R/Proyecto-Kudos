<?php

namespace App\Actions;

use App\Models\ItemComment;
use App\Models\Report;
use App\Models\User;
use App\Notifications\ContentRemovedNotification;
use App\Services\ModerationAuditLogger;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Moderación directa de un comentario desde el panel: ocultarlo, restaurarlo o borrarlo.
 * Las tres decisiones quedan en el registro de moderación. Ocultar y borrar avisan además al autor
 * de qué contenido, por qué y cómo reclamar (exposición de motivos de la DSA).
 */
class ModerateCommentAction
{
    public const ACTION_HIDE = 'hide';

    public const ACTION_UNHIDE = 'unhide';

    public const ACTION_DELETE = 'delete';

    public function __construct(
        protected ModerationAuditLogger $moderationAuditLogger,
    ) {}

    /**
     * @param  string  $action  self::ACTION_HIDE | self::ACTION_UNHIDE | self::ACTION_DELETE
     *
     * @throws \Throwable
     */
    public function execute(ItemComment $comment, User $admin, string $action, ?string $reason = null): void
    {
        DB::transaction(function () use ($comment, $admin, $action, $reason) {
            $this->apply($comment, $admin, $action, $reason);

            $this->moderationAuditLogger->logCommentModeration($comment, $admin, $action, $reason);

            // Restaurar no retira nada, así que no hay nada que explicar al autor.
            if ($action !== self::ACTION_UNHIDE) {
                $this->notifyAuthor($comment, $admin, $action, $reason);
            }
        });
    }

    /**
     * Solo cambia el estado del comentario, sin registro ni aviso. ResolveReportAction lo usa así
     * porque registra la resolución del reporte y avisa al autor por su cuenta.
     */
    public function apply(ItemComment $comment, User $admin, string $action, ?string $reason = null): void
    {
        match ($action) {
            self::ACTION_HIDE => $comment->hideByModerator($admin->id, $reason),
            self::ACTION_UNHIDE => $comment->update([
                'is_hidden' => false,
                'hidden_reason' => null,
                'hidden_by' => null,
            ]),
            self::ACTION_DELETE => $comment->delete(),
            default => throw new InvalidArgumentException("Acción de moderación desconocida: {$action}."),
        };
    }

    private function notifyAuthor(ItemComment $comment, User $admin, string $action, ?string $reason): void
    {
        $author = $comment->user;

        // Un comentario anonimizado (cuenta borrada) no tiene a quién avisar, y el admin que modera lo suyo ya lo sabe.
        if ($author === null || $author->is($admin)) {
            return;
        }

        $author->notify(new ContentRemovedNotification(
            'comment',
            (string) $comment->content,
            $action === self::ACTION_HIDE ? ContentRemovedNotification::ACTION_HIDDEN : ContentRemovedNotification::ACTION_DELETED,
            // El panel no obliga a dar un motivo (borrar ni lo pide): se usa la etiqueta genérica de los reportes.
            filled($reason) ? $reason : Report::REASON_LABELS['otro'],
            config('kudos.legal.contact_email'),
        ));
    }
}
