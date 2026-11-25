<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para representar un comentario de ítem.
 * Incluye información del comentario, su estado de oculto, razón de ocultación (si el usuario es admin o dueño), y datos del usuario que lo hizo.
 * Se utiliza para mostrar los comentarios de un ítem, con detalles específicos según el rol del usuario que hace la solicitud.
 */
class ItemCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isAdmin = $user instanceof User && $user->hasRole('admin');
        $isOwner = $user instanceof User && $user->id === $this->user_id;

        return [
            'id' => $this->id,
            'content' => $this->content,
            // F3-02: respuesta (parent_id), reseña (is_review + nota del voto) y respuestas anidadas (solo en el listado).
            'parent_id' => $this->parent_id,
            'is_review' => $this->vote_id !== null,
            'score' => $this->vote_id !== null && $this->vote?->type === Vote::TYPE_VOTE ? $this->vote->score : null,
            'is_hidden' => (bool) $this->is_hidden,
            'hidden_reason' => $this->when($isAdmin || $isOwner, $this->hidden_reason),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'user' => [
                'id' => $this->user_id,
                'name' => $this->user?->name,
	              'avatar' => $this->user?->profile?->avatar,
            ],
            'can_edit' => $this->when($user instanceof User, fn () => $user->can('update', $this->resource)),
            'can_delete' => $this->when($user instanceof User, fn () => $user->can('delete', $this->resource)),
            'replies' => ItemCommentResource::collection($this->whenLoaded('replies')),
        ];
    }
}
