<?php

namespace App\Http\Requests\Admin;

use App\Models\Proposal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('review', $this->route('proposal')) ?? false;
    }

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
