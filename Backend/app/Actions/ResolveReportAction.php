<?php

namespace App\Actions;

use App\Contracts\Media\MediaStorageInterface;
use App\Models\ItemComment;
use App\Models\Report;
use App\Models\User;
use App\Models\UserList;
use App\Notifications\ContentRemovedNotification;
use App\Services\HomeFeedService;
use App\Services\ModerationAuditLogger;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Resuelve un reporte de contenido (F3-04): descartarlo, ocultar el contenido o borrarlo.
 * Ocultar o borrar avisa al autor (ContentRemovedNotification) y cierra los demás reportes pendientes del mismo contenido.
 */
class ResolveReportAction
{
    public function __construct(
        protected ModerationAuditLogger $moderationAuditLogger,
        protected MediaStorageInterface $mediaStorage,
        protected ModerateCommentAction $moderateComment,
    ) {}

    /**
     * @return array{report: Report, content_action: string, closed_reports: int}
     *
     * @throws ConflictHttpException si el reporte ya está resuelto.
     * @throws \Throwable
     */
    public function execute(Report $report, User $admin, string $action, ?string $note): array
    {
        return DB::transaction(function () use ($report, $admin, $action, $note) {
            $locked = Report::query()->lockForUpdate()->findOrFail($report->id);

            if ($locked->status !== Report::STATUS_PENDING) {
                throw new ConflictHttpException('Este reporte ya está resuelto.');
            }

            if ($action === Report::ACTION_DISMISS) {
                $locked->update([
                    'status' => Report::STATUS_DISMISSED,
                    'resolved_by' => $admin->id,
                    'resolution_note' => $note,
                    'resolved_at' => now(),
                ]);

                $this->moderationAuditLogger->logReportResolution($locked, $admin, $action, $note, [
                    'content_action' => 'none',
                    'closed_report_ids' => [$locked->id],
                ]);

                return [
                    'report' => $this->reloadForAdmin($locked),
                    'content_action' => 'none',
                    'closed_reports' => 1,
                ];
            }

            // Lo que recibe el autor como motivo: la etiqueta del motivo y la nota (solo la nota con «otro»).
            $authorReason = $locked->reason === 'otro' ? (string) $note : $locked->reasonLabel().'. '.$note;

            $contentAction = 'none';
            $author = null;
            $contentType = null;
            $excerpt = '';

            $reportable = $locked->resolveReportable();

            if ($reportable instanceof ItemComment) {
                $contentType = 'comment';
                $excerpt = (string) $reportable->content;
                $author = $reportable->user;

                $this->moderateComment->apply(
                    $reportable,
                    $admin,
                    $action === Report::ACTION_HIDE ? ModerateCommentAction::ACTION_HIDE : ModerateCommentAction::ACTION_DELETE,
                    $authorReason,
                );
                $contentAction = $action === Report::ACTION_HIDE ? 'hidden' : 'deleted';
            } elseif ($reportable instanceof UserList) {
                $contentType = 'list';
                $excerpt = (string) $reportable->title;
                $author = $reportable->user;

                if ($action === Report::ACTION_HIDE) {
                    // F3-01: se marca para que el dueño no pueda volver a publicarla.
                    $reportable->forceFill([
                        'visibility' => UserList::VISIBILITY_PRIVATE,
                        'hidden_by_moderation_at' => now(),
                    ])->save();
                } else {
                    $reportable->delete();
                }
                HomeFeedService::forgetCache();
                $contentAction = $action === Report::ACTION_HIDE ? 'hidden' : 'deleted';
            } elseif ($reportable instanceof User) {
                // Un perfil no se oculta (lo rechaza el Form Request): borrar vacía su contenido público.
                $contentType = 'profile';
                $author = $reportable;
                $profile = $reportable->profile;
                $excerpt = (string) ($profile?->biography ?: $reportable->name);

                if ($profile !== null) {
                    $avatar = $profile->avatar;
                    $profile->update(['biography' => null, 'social_links' => null, 'city' => null, 'avatar' => null]);

                    if (! empty($avatar)) {
                        // Después del commit: si la transacción se deshiciera, el perfil seguiría apuntando a este fichero.
                        DB::afterCommit(fn () => $this->mediaStorage->delete('public', $avatar));
                    }
                }
                $contentAction = 'deleted';
            }

            $ids = Report::query()
                ->pending()
                ->where('reportable_type', $locked->reportable_type)
                ->where('reportable_id', $locked->reportable_id)
                ->pluck('id');

            Report::query()->whereIn('id', $ids)->update([
                'status' => Report::STATUS_RESOLVED,
                'resolved_by' => $admin->id,
                'resolution_note' => $note,
                'resolved_at' => now(),
                'updated_at' => now(),
            ]);

            $this->moderationAuditLogger->logReportResolution($locked, $admin, $action, $note, [
                'content_action' => $contentAction,
                'closed_report_ids' => $ids->all(),
            ]);

            if ($contentAction !== 'none' && $author !== null) {
                $author->notify(new ContentRemovedNotification(
                    $contentType,
                    $excerpt,
                    $action === Report::ACTION_HIDE ? ContentRemovedNotification::ACTION_HIDDEN : ContentRemovedNotification::ACTION_DELETED,
                    $authorReason,
                    config('kudos.legal.contact_email'),
                ));
            }

            return [
                'report' => $this->reloadForAdmin($locked),
                'content_action' => $contentAction,
                'closed_reports' => $ids->count(),
            ];
        });
    }

    private function reloadForAdmin(Report $report): Report
    {
        return Report::query()->withAdminRelations()->findOrFail($report->id);
    }
}
