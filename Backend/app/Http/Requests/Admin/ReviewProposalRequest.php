<?php

namespace App\Http\Requests\Admin;

use App\Models\Proposal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Gestiona y valida las peticiones de moderación de ítems por parte de administradores.
 */
class ReviewProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('review', $this->route('proposal')) ?? false;
    }

    /**
     * Define las reglas de validación para revisar una propuesta.
     * - El campo 'status' es obligatorio y debe ser uno de los valores permitidos (accepted, rejected, changes_requested).
     * - El campo 'admin_notes' es opcional, pero si se proporciona, debe ser una cadena de texto con un máximo de 2000 caracteres.
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    Proposal::STATUS_ACCEPTED,
                    Proposal::STATUS_REJECTED,
                    Proposal::STATUS_CHANGES_REQUESTED,
                ]),
            ],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Agrega validaciones adicionales después de las reglas básicas.
     * - Verifica que la propuesta no esté eliminada (trashed).
     * - Verifica que la propuesta esté en estado 'pending' antes de permitir su revisión.
     * - Si el estado es 'rejected' o 'changes_requested', asegura que se proporcionen notas administrativas.
     */
	public function withValidator($validator): void
	{
		$validator->after(function ($validator) {
			$proposal = $this->route('proposal');
			$status = $this->input('status');
			$notes = $this->input('admin_notes');

			if ($proposal) {
				if ($proposal->trashed()) {
					$validator->errors()->add('proposal', 'No se puede revisar una propuesta eliminada.');
				}

				if ($proposal->status !== Proposal::STATUS_PENDING) {
					$validator->errors()->add('proposal', 'Solo se pueden revisar propuestas en estado pending.');
				}
			}

			if (in_array($status, [Proposal::STATUS_REJECTED, Proposal::STATUS_CHANGES_REQUESTED], true) && empty($notes)) {
				$validator->errors()->add('admin_notes', 'admin_notes es obligatorio para rejected o changes_requested.');
			}
		});
	}
}
