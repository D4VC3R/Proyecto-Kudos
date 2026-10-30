<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Candidato de la fuente externa devuelto por la búsqueda bajo demanda (F1-07). No es un ítem de Kudos:
 * `exists` indica si ya hay uno enlazado a la fuente e `item_id` es su id solo si está activo y sin borrar.
 * Representa un array de CatalogSearch::search().
 */
class CatalogSearchResultResource extends JsonResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
			'external_id' => $this->resource['external_id'] ?? null,
			'name' => $this->resource['name'] ?? null,
			'original_title' => $this->resource['original_title'] ?? null,
			'released_at' => $this->resource['released_at'] ?? null,
			'year' => $this->resource['year'] ?? null,
			'description' => $this->resource['description'] ?? null,
			'source_url' => $this->resource['source_url'] ?? null,
			'exists' => (bool) ($this->resource['exists'] ?? false),
			'item_id' => $this->resource['item_id'] ?? null,
		];
	}
}
