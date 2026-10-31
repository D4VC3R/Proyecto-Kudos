<?php

namespace App\Http\Resources;

use App\Models\ListItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para representar una lista (F2-09).
 * Se usa igual en la portada, en la página pública /listas/{slug} y en el panel de administración.
 * No expone al autor: llegará con los perfiles de la F3.
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
