<?php

namespace App\Http\Resources;

use App\Models\Item;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * Resource para representar un ítem en listados.
 * Incluye información básica del ítem, su categoría, estado de votación del usuario, y permisos de edición/eliminación.
 * Se utiliza para mostrar ítems en vistas de listado, proporcionando la información esencial y acciones disponibles según el rol del usuario.
 * Desde la F1-06 incluye original_title, released_at y los atributos de tarjeta (show_in_card).
 */
class ItemListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Item $item */
        $item = $this->resource;
        $user = $this->resolveViewer($request);
        $isAdmin = $user instanceof User && $user->hasRole('admin');

	    return [
		    'id' => $item->id,
		    'name' => $item->name,
		    'description' => $item->description,
		    'images' => collect($item->images ?? [])
			    ->take(1)
			    ->map(fn ($img) => [
				    'variants' => [
					    'thumb' => $img['variants']['thumb'] ?? null,
				    ],
				    'meta' => $img['meta'] ?? null,
				    'alt' => $img['alt'] ?? null,
			    ])->toArray(),
		    'status' => $item->status,
		    'vote_avg' => (float) $item->vote_avg,
		    'vote_count' => (int) $item->vote_count,
		    'original_title' => $item->original_title,
		    'released_at' => $item->released_at?->toDateString(),
		    'attributes' => (object) $this->cardAttributes($item),
            'user_vote' => $this->when(
                $user && $item->relationLoaded('userVote') && $item->userVote,
                fn () => [
                    'id' => $item->userVote->id,
                    'score' => $item->userVote->score,
                    'voted_at' => $item->userVote->created_at?->toIso8601String(),
                ]
            ),

            'can_vote' => $this->resolveCanVote($user, $item),
            'can_edit' => $this->when($user instanceof User, fn () => $isAdmin || $item->creator_id === $user->id),
            'can_delete' => $this->when($user instanceof User, fn () => $isAdmin),

            'category' => new CategoryResource($this->whenLoaded('category')),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $item->creator->id,
                'name' => $item->creator->name,
            ]),
        ];
    }

    protected function resolveCanVote(?User $user, Item $item): bool
    {
        if (!$user || $item->status !== Item::STATUS_ACTIVE) {
            return false;
        }

        if ($item->relationLoaded('userVote')) {
            return $item->userVote === null;
        }

        return false;
    }

    /**
     * Atributos que muestra la tarjeta: solo las claves con show_in_card del attribute_schema de la categoría,
     * en el orden del esquema (F1-06). Si la categoría no está cargada o no tiene esquema, ninguno.
     * Se lee con getAttribute() porque $item->attributes es la propiedad interna de Eloquent.
     */
    protected function cardAttributes(Item $item): array
    {
        $attributes = $item->getAttribute('attributes');
        $schema = $item->relationLoaded('category') ? $item->category?->attribute_schema : null;

        if (!is_array($attributes) || $attributes === [] || !is_array($schema)) {
            return [];
        }

        $card = [];
        foreach ($schema as $field) {
            $key = is_array($field) ? ($field['key'] ?? null) : null;
            if ($key !== null && !empty($field['show_in_card']) && array_key_exists($key, $attributes)) {
                $card[$key] = $attributes[$key];
            }
        }

        return $card;
    }

    protected function resolveViewer(Request $request): ?User
    {
        $requestUser = $request->user();
        if ($requestUser instanceof User) {
            return $requestUser;
        }

        $sanctumUser = Auth::guard('sanctum')->user();

        return $sanctumUser instanceof User ? $sanctumUser : null;
    }
}
