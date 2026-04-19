<?php

namespace App\Http\Requests;

use App\Models\Vote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListMyVotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::in([Vote::TYPE_VOTE, Vote::TYPE_SKIP])],
            'category_slug' => ['sometimes', 'nullable', 'string', 'exists:categories,slug'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
