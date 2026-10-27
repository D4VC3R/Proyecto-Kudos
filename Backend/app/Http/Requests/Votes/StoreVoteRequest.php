<?php

namespace App\Http\Requests\Votes;

use App\Models\Item;
use App\Models\Vote;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Gestiona y valida las peticiones de creación de votos por parte de usuarios.
 */
class StoreVoteRequest extends FormRequest
{
    /**
     * Autoriza la petición de creación de voto asegurando que el usuario esté autenticado y tenga permiso para votar en el ítem especificado.
     */
	public function authorize(): bool
	{
		if (!$this->user()) {
			return false;
		}

		$itemId = $this->input('item_id');
		if (!is_string($itemId) || $itemId === '') {
			return true; // Dejamos que rules() atrape la falta de ID
		}

		$item = Item::query()->select(['id', 'status'])->find($itemId);
		if (!$item) {
			return true; // Dejamos que rules() atrape el exists
		}

		return $this->user()->can('create', [Vote::class, $item]);
	}

    /**
     * Define las reglas de validación para la creación de votos.
     * Asegura que el ID del ítem sea válido, que el tipo de voto sea correcto y que la puntuación se maneje adecuadamente según el tipo.
     */
	public function rules(): array
	{
		return [
			// withoutTrashed(): un ítem borrado (soft delete) se rechaza igual para vote y skip (F0-21)
			'item_id' => ['required', 'uuid', Rule::exists('items', 'id')->withoutTrashed()],
			'type' => ['required', Rule::in([Vote::TYPE_VOTE, Vote::TYPE_SKIP])],
			'score' => [
				'nullable',
				'numeric',
				'min:0',
				'max:10',
				'multiple_of:0.5',
				Rule::requiredIf(fn () => $this->input('type') === Vote::TYPE_VOTE),
				Rule::prohibitedIf(fn () => $this->input('type') === Vote::TYPE_SKIP),
			],
		];
	}

    /**
     * Mensajes de error personalizados para las validaciones de puntuación.
     */
	public function messages(): array
	{
		return [
			'item_id.exists' => 'El ítem no existe o ya no está disponible.',
			'score.multiple_of' => 'La puntuación debe ir en pasos de medio punto (por ejemplo, 7 o 7,5).',
			'score.required' => 'No se ha registrado puntuación asociada al voto.',
			'score.prohibited' => 'No se ha registrado la puntuación: votación omitida.',
		];
	}
}
