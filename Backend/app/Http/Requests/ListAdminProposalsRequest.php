<?php

namespace App\Http\Requests;

use App\Models\Proposal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAdminProposalsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reviewAny', Proposal::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::in([
                Proposal::STATUS_PENDING,
                Proposal::STATUS_ACCEPTED,
                Proposal::STATUS_REJECTED,
                Proposal::STATUS_CHANGES_REQUESTED,
            ])],
            'creator_id' => ['sometimes', 'uuid', 'exists:users,id'],
            'reviewed_by' => ['sometimes', 'nullable', 'uuid', 'exists:users,id'],
            'category_id' => ['sometimes', 'uuid', 'exists:categories,id'],
            'search' => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}

