<?php

namespace App\Http\Requests\Votes;

use App\Models\Item;
use App\Models\Vote;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/* * Gestiona y valida las peticiones de actualización de votos por parte de usuarios.
 */
class UpdateVoteRequest extends FormRequest
{

	public function authorize(): bool
	{
		return $this->user()?->can('update', $this->route('vote')) ?? false;
	}

    /*
     * Define las reglas de validación para la actualización de votos, permitiendo solo ciertos tipos de voto y rangos de puntuación.
     */
	public function rules(): array
	{
		return [
			'type' => ['nullable', Rule::in([Vote::TYPE_VOTE, Vote::TYPE_SKIP])],
			'score' => [
				'nullable',
				'numeric',
				'min:0',
				'max:10',
			],
		];
	}

    /*
     * Verifica que el ítem asociado al voto esté activo y que el tipo de voto sea correcto para permitir la edición de la puntuación.
     */
	public function withValidator($validator): void
	{
		$validator->after(function ($validator) {
			$vote = $this->route('vote');

			if ($vote) {
				if ($vote->item && $vote->item->status !== Item::STATUS_ACTIVE) {
					$validator->errors()->add('vote', 'No se puede actualizar el voto porque el item está inactivo.');
				}

				if ($vote->type !== Vote::TYPE_VOTE) {
					$validator->errors()->add('vote', 'Solo se puede editar la puntuación de una votación ya emitida.');
				}
			}
		});
	}
}
