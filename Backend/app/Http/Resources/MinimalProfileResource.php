<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para representar la tarjeta de perfil en el header de la aplicación.
 * Incluye el ID, nombre, avatar, total de kudos y posición en el ranking.
 * La posición la calcula el controlador con User::getKudosRank(), con el mismo desempate que el ranking.
 */
class MinimalProfileResource extends JsonResource
{
    public function __construct(User $resource, private readonly int $rankingPosition)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar' => $this->profile?->avatar ?? null,
            'total_kudos' => $this->total_kudos,
            'ranking_position' => $this->rankingPosition,
        ];
    }
}
