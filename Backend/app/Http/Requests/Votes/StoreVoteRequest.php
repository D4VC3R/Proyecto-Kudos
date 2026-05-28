<?php

namespace App\Http\Requests\Votes;

use App\Models\Item;
use App\Models\Vote;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVoteRequest extends FormRequest
{
	/**
	 * Determine if the user is authorized to make this request.
	 */
	public function authorize(): bool
	{
		if (!$this->user()) {
			return false;
		}

		$itemId = $this->input('item_id');
		if (!is_string($itemId) || $itemId === '') {
			return true; // Dejamos que rules() atrape la falta de ID[cite: 46]
		}

		$item = Item::query()->select(['id', 'status'])->find($itemId);
		if (!$item) {
			return true; // Dejamos que rules() atrape el exists[cite: 46]
		}

		return $this->user()->can('create', [Vote::class, $item]);
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array<string, ValidationRule|array<mixed>|string>
	 */
	public function rules(): array
	{
		return [
			'item_id' => ['required', 'uuid', Rule::exists('items', 'id')],
			'type' => ['required', Rule::in([Vote::TYPE_VOTE, Vote::TYPE_SKIP])],
			'score' => [
				'nullable',
				'numeric',
				'min:0',
				'max:10',
				Rule::requiredIf(fn () => $this->input('type') === Vote::TYPE_VOTE),
				Rule::prohibitedIf(fn () => $this->input('type') === Vote::TYPE_SKIP),
			],
		];
	}

	public function messages(): array
	{
		return [
			'score.required' => 'No se ha registrado puntuación asociada al voto.',
			'score.prohibited' => 'No se ha registrado la puntuación: votación omitida.',
		];
	}

	/**
	 * Validaciones de negocio transferidas desde el servicio.
	 */
	public function withValidator($validator): void
	{
		$validator->after(function ($validator) {
			$itemId = $this->input('item_id');
			if (!$itemId) {
				return;
			}

			$item = Item::find($itemId);

			// Regla de estado de negocio
			if ($item && $item->status !== Item::STATUS_ACTIVE) {
				$validator->errors()->add('item_id', 'No se puede votar un item inactivo.');
			}
		});
	}
}