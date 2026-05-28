<?php

namespace App\Http\Requests\Votes;

use App\Models\Item;
use App\Models\Vote;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVoteRequest extends FormRequest
{
	/**
	 * Determine if the user is authorized to make this request.
	 */
	public function authorize(): bool
	{
		return $this->user()?->can('update', $this->route('vote')) ?? false;
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array<string, ValidationRule|array|string>
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

	/**
	 * Validaciones de negocio transferidas desde el servicio.
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