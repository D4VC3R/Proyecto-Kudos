<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use App\Models\User;

/**
 * Resource para representar la tarjeta de perfil en el header de la aplicación.
 * Incluye el ID, nombre, avatar, total de kudos y posición en el ranking.
 *
 */
class MinimalProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $rankingPosition = User::where('total_kudos', '>', $this->total_kudos)->count() + 1;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar' => $this->profile?->avatar ?? null,
            'total_kudos' => $this->total_kudos,
            'ranking_position' => $rankingPosition,
        ];
    }
}

