<?php

namespace App\Http\Requests\Proposals;

use App\Models\Proposal;
use App\Rules\FileOrUrlRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Proposal::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['required_with:images', new FileOrUrlRule()],
            'category_id' => ['required', 'uuid', 'exists:categories,id'],
        ];
    }

}
