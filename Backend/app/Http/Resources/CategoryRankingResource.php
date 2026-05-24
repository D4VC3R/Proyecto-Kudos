<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para representar el ranking de ítems por categoría.
 * Combina la información de la categoría con una colección de ítems ordenados por votos.
 * Queda estructurado para ser usado en la vista de ranking por categoría, mostrando la categoría y su ranking de ítems en una sola respuesta.
 */
class CategoryRankingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'category' => new CategoryResource($this->resource['category']),
            'ranking' => ItemListResource::collection($this->resource['ranking']),
        ];
    }
}


