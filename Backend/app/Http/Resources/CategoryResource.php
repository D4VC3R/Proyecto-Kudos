<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
		    'items_count' => $this->when(isset($this->items_count), $this->items_count),
		    'created_at' => $this->created_at?->toIso8601String(),
		    'updated_at' => $this->updated_at?->toIso8601String(),
	    ];
    }
}
