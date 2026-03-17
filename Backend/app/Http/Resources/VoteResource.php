<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'score' => $this->score,
            'item_id' => $this->item_id,
            'voted_at' => $this->created_at?->toIso8601String(),
            'item' => $this->whenLoaded('item', [
                'id' => $this->item?->id,
                'name' => $this->item?->name,
                'category_id' => $this->item?->category_id,
                'category' => $this->when($this->item?->relationLoaded('category'), [
                    'id' => $this->item?->category?->id,
                    'name' => $this->item?->category?->name,
                    'slug' => $this->item?->category?->slug,
                ]),
            ]),
        ];
    }
}

