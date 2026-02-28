<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
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
		    'image' => $this->image ? asset('storage/' . $this->image) : null,
		    'state' => $this->state,
		    'vote_avg' => round($this->vote_avg, 2),
		    'vote_count' => $this->vote_count,

		    // Relaciones opcionales
		    'creator' => $this->whenLoaded('creator', function () {
			    return [
				    'id' => $this->creator->id,
				    'name' => $this->creator->name,
			    ];
		    }),

		    'category' => $this->whenLoaded('category', function () {
			    return [
				    'id' => $this->category->id,
				    'name' => $this->category->name,
				    'slug' => $this->category->slug,
			    ];
		    })
	    ];
    }
}
