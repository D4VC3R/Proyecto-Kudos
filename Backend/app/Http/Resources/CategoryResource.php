<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para representar una categoría sin incluir sus ítems.
 * Se utiliza principalmente para listar categorías, mostrando solo la información básica y el conteo de ítems.
 * Desde la F1-06 expone attribute_schema, icon y sort_order (la categoría anidada en un ítem puede llevarlos vacíos si se cargó con columnas limitadas).
 */
class CategoryResource extends JsonResource
{


	/**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
	    return [
		    'id' => $this->id,
		    'name' => $this->name,
		    'description' => $this->description,
		    'slug' => $this->slug,
		    'image' => $this->image,
		    'items_count' => $this->items_count,
		    'attribute_schema' => $this->attribute_schema ?? [],
		    'icon' => $this->icon,
		    'sort_order' => $this->sort_order,
	    ];
    }
}
