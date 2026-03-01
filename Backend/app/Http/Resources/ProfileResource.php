<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
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
		    'avatar' => $this->avatar,
		    'biography' => $this->biography,
		    'social_links' => $this->social_links,
		    'city' => $this->city,
		    'birthdate' => $this->birthdate ? $this->birthdate->format('Y-m-d') : null,
	    ];
    }
}
