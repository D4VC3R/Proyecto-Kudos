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

	/**
	 * Normaliza el tipo destino antes de validar (F2-02): skip pasa a not_interested y, si no se envía,
	 * el destino es el tipo actual del voto (también normalizado).
	 */
	protected function prepareForValidation(): void
	{
		$type = $this->input('type');

		if ($type === null || $type === '') {
			$type = $this->currentType();
		}

		if ($type === Vote::TYPE_SKIP) {
			$type = Vote::TYPE_NOT_INTERESTED;
		}

		$this->merge(['type' => $type]);
	}

	/**
	 * Tipo actual del voto, con el valor heredado skip normalizado a not_interested.
	 */
	protected function currentType(): ?string
	{
		$type = $this->route('vote')?->type;

		return $type === Vote::TYPE_SKIP ? Vote::TYPE_NOT_INTERESTED : $type;
	}

	public function authorize(): bool
	{
		return $this->user()?->can('update', $this->route('vote')) ?? false;
	}

    /*
     * Define las reglas de validación para la actualización de votos: el tipo destino (vote, want o not_interested; skip se
     * acepta y se normaliza) y la puntuación, que es obligatoria al pasar a valoración desde want/not_interested y está
     * prohibida en want y not_interested.
     */
	public function rules(): array
	{
		return [
			// skip también se acepta: prepareForValidation() lo convierte en not_interested
			'type' => ['required', Rule::in([Vote::TYPE_VOTE, Vote::TYPE_WANT, Vote::TYPE_NOT_INTERESTED])],
			'score' => [
				'nullable',
				'numeric',
				'min:0',
				'max:10',
				'multiple_of:0.5',
				// Pasar a valoración desde want/not_interested exige nota; cambiar la nota de un vote la deja opcional
				Rule::requiredIf(fn () => $this->input('type') === Vote::TYPE_VOTE && $this->currentType() !== Vote::TYPE_VOTE),
				Rule::prohibitedIf(fn () => in_array($this->input('type'), [Vote::TYPE_WANT, Vote::TYPE_NOT_INTERESTED], true)),
			],
		];
	}

    /*
     * Rechaza el cambio si el ítem ya no está disponible o inactivo, y si se intenta convertir una valoración en
     * want/not_interested (hay que borrarla y crear el voto de nuevo).
     */
	public function withValidator($validator): void
	{
		$validator->after(function ($validator) {
			$vote = $this->route('vote');

			if ($vote) {
				// Un ítem borrado (soft delete) no admite cambios, igual que al emitir (F0-21)
				if (!$vote->item) {
					$validator->errors()->add('vote', 'No se puede actualizar el voto porque el ítem ya no está disponible.');
				}

				if ($vote->item && $vote->item->status !== Item::STATUS_ACTIVE) {
					$validator->errors()->add('vote', 'No se puede actualizar el voto porque el item está inactivo.');
				}

				// Una valoración no vuelve a want/not_interested: hay que borrarla y crear el voto de nuevo (F2-02)
				if ($vote->type === Vote::TYPE_VOTE && $this->input('type') !== Vote::TYPE_VOTE) {
					$validator->errors()->add('vote', 'Una valoración no se puede convertir en "me interesa" ni en "no me interesa": bórrala y vuelve a crearla.');
				}
			}
		});
	}
/**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'El valor seleccionado para tipo no es válido.',
            'score.required' => 'Para valorar el ítem hace falta una puntuación.',
            'score.prohibited' => 'Solo se puntúa al valorar: "me interesa" y "no me interesa" no llevan puntuación.',
            'score.numeric' => 'El campo puntuación debe ser numérico.',
            'score.min' => 'El campo puntuación debe tener al menos 0 caracteres/elementos.',
            'score.max' => 'El campo puntuación no puede superar los 10 caracteres/elementos.',
            'score.multiple_of' => 'La puntuación debe ir en pasos de medio punto (por ejemplo, 7 o 7,5).',
        ];
    }
}
