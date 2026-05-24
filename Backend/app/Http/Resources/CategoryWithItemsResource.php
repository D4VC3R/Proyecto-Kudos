<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para representar una categoría con sus ítems relacionados.
 * Incluye información detallada de la categoría y una colección de ítems asociados.
 * Se usa en las vistas de exploración de categoría para mostrar todos sus ítems en una respuesta.
 */
class CategoryWithItemsResource extends JsonResource
{

    public function toArray(Request $request): array
    {
            return [
                    'id' => $this->id,
                    'name' => $this->name,
                    'description' => $this->description,
                    'slug' => $this->slug,
                    'image' => $this->image,
                    'items_count' => $this->items_count,
                    'items' => ItemListResource::collection($this->whenLoaded('items')),
                    'created_at' => $this->created_at?->toIso8601String(),
                    'updated_at' => $this->updated_at?->toIso8601String(),
            ];
    }
}
