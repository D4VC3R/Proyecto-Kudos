<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->loadMissing('user:id,total_kudos,creations_accepted');

        return [
            'id' => $this->id,
            'avatar' => $this->avatar,
            'biography' => $this->biography,
            'social_links' => $this->social_links,
            'city' => $this->city,
            'birthdate' => $this->birthdate ? $this->birthdate->format('Y-m-d') : null,
            'total_kudos' => $this->user?->total_kudos ?? 0,
            'creations_accepted' => $this->user?->creations_accepted ?? 0,
        ];
    }
}
