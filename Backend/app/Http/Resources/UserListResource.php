<?php

namespace App\Http\Resources;

use App\Models\ListItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para representar una lista (F2-09).
 * Se usa igual en la portada, en la página pública /listas/{slug} y en el panel de administración.
 * Desde la F3-03 añade `author` (solo si se ha cargado `user`): el perfil público del autor, o `null` si es editorial
 * o el perfil no es público. La F3-01 añade `is_hidden_by_moderation`,
 * `can_edit`, `liked_by_me` y `contains_item`.
 */
class UserListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'visibility' => $this->visibility,
            'is_ranked' => (bool) $this->is_ranked,
            'is_editorial' => (bool) $this->is_editorial,
            'items_count' => (int) $this->items_count,
            'likes_count' => (int) $this->likes_count,
            'cover_item_id' => $this->cover_item_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            // F3-01. Ocultada por moderación: el dueño no puede volver a publicarla.
            'is_hidden_by_moderation' => $this->hidden_by_moderation_at !== null,
            // Solo con sesión: si quien mira puede gestionarla desde "Mis listas" (dueño y lista no editorial).
            'can_edit' => $this->when($request->user() !== null, fn () => ! $this->is_editorial && $request->user()->id === $this->user_id),
            // Solo si el controlador lo ha cargado (página pública con sesión).
            'liked_by_me' => $this->when(array_key_exists('liked_by_me', $this->resource->getAttributes()), fn () => (bool) $this->resource->getAttribute('liked_by_me')),
            // Solo en "Mis listas" con ?item_id=: si la lista ya contiene ese ítem.
            'contains_item' => $this->when(array_key_exists('contains_item', $this->resource->getAttributes()), fn () => (bool) $this->resource->getAttribute('contains_item')),
            // F3-03. Autor de una lista de usuario, solo con perfil público y nombre de usuario (privacidad por defecto).
            'author' => $this->whenLoaded('user', fn () => ! $this->is_editorial && $this->user?->hasPublicProfile() ? [
                'username' => $this->user->username,
                'name' => $this->user->name,
                'avatar' => $this->user->profile?->avatar,
            ] : null),
            // Entradas en orden: posición, nota y el ítem como tarjeta. Solo si se ha cargado listItems.
            'items' => $this->whenLoaded('listItems', fn () => $this->listItems
                ->filter(fn (ListItem $entry) => $entry->relationLoaded('item') && $entry->item !== null)
                ->values()
                ->map(fn (ListItem $entry) => [
                    'id' => $entry->id,
                    'position' => $entry->position,
                    'note' => $entry->note,
                    'item' => new ItemListResource($entry->item),
                ])->all()),
        ];
    }
}
