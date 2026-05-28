<?php

namespace App\Http\Resources;

use App\Models\User;
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
        $user = $request->user('sanctum') ?? $request->user();
        $isAdmin = $user instanceof User && $user->hasRole('admin');
        $isOwner = $user instanceof User && $user->id === $this->user_id;

        return [
            'id' => $this->id,
            'content' => $this->content,
            'is_hidden' => (bool) $this->is_hidden,
            'hidden_reason' => $this->when($isAdmin || $isOwner, $this->hidden_reason),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'user' => [
                'id' => $this->user_id,
                'name' => $this->user?->name,
	              'avatar' => $this->user?->profile?->avatar,
            ],
            'can_edit' => $this->when($user instanceof User, $isAdmin || $isOwner),
            'can_delete' => $this->when($user instanceof User, $isAdmin || $isOwner),
        ];
    }
}
