<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\ReportResource;
use App\Models\ItemComment;
use App\Models\Report;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Reporte de contenido para la cola del administrador (F3-04): añade la resolución, las personas
 * implicadas y un resumen del contenido reportado (`content`, null si ya no existe).
 *
 * @mixin Report
 */
class AdminReportResource extends ReportResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'resolution_note' => $this->resolution_note,
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'reporter' => $this->person($this->reporter),
            'resolver' => $this->person($this->resolver),
            'content' => $this->content(),
        ];
    }

    /**
     * @return array{id: string, name: string}|null
     */
    private function person(?User $user): ?array
    {
        return $user ? ['id' => $user->id, 'name' => $user->name] : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function content(): ?array
    {
        if (! $this->relationLoaded('reportable') || $this->reportable === null) {
            return null;
        }

        $reportable = $this->reportable;

        if ($reportable instanceof ItemComment) {
            $slug = $reportable->item?->category?->slug;

            return [
                'excerpt' => Str::limit((string) $reportable->content, 200),
                'author' => $this->person($reportable->user),
                'is_hidden' => (bool) $reportable->is_hidden,
                'is_deleted' => $reportable->trashed(),
                'url' => $slug ? "/{$slug}/item/{$reportable->item_id}" : null,
            ];
        }

        if ($reportable instanceof UserList) {
            return [
                'excerpt' => $reportable->title,
                'author' => $this->person($reportable->user),
                'is_hidden' => $reportable->visibility === UserList::VISIBILITY_PRIVATE,
                'is_deleted' => $reportable->trashed(),
                'url' => $reportable->trashed() ? null : "/listas/{$reportable->slug}",
            ];
        }

        if ($reportable instanceof User) {
            $biography = $reportable->profile?->biography;

            return [
                'excerpt' => $reportable->name.($biography ? ' — '.Str::limit($biography, 150) : ''),
                'author' => $this->person($reportable),
                'is_hidden' => false,
                'is_deleted' => false,
                // F3-03: enlace al perfil público, si lo es; un perfil privado daría 404 también al admin.
                'url' => $reportable->hasPublicProfile() ? "/u/{$reportable->username}" : null,
            ];
        }

        return null;
    }
}
