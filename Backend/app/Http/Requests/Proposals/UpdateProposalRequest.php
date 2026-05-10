<?php

namespace App\Http\Requests\Proposals;

use App\Models\Proposal;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('proposal'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'min:20', 'max:2000'],
            'images' => ['sometimes', 'nullable', 'array', 'max:10'],
            'images.*.path' => ['sometimes', 'required_with:images', 'string', 'max:500'],
            'images.*.disk' => ['sometimes', 'required_with:images', 'string', 'in:public'],
            'images.*.alt' => ['sometimes', 'nullable', 'string', 'max:255'],
            'images.*.order' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'category_id' => ['sometimes', 'required', 'uuid', 'exists:categories,id'],
        ];
    }

	public function withValidator($validator): void
	{
		$validator->after(function ($validator) {

			$proposal = $this->route('proposal');

			if ($proposal) {
				if ($proposal->trashed()) {
					$validator->errors()->add('proposal', 'No se puede editar una propuesta eliminada.');
				}

				if (!in_array($proposal->status, [Proposal::STATUS_CHANGES_REQUESTED, Proposal::STATUS_PENDING], true)) {
					$validator->errors()->add('proposal', 'Solo se pueden editar o reenviar propuestas en estado pending o changes_requested.');
				}
			}

			$categoryId = $this->input('category_id', $proposal?->category_id);
			if (!is_string($categoryId) || $categoryId === '') return;

		});
	}
}
