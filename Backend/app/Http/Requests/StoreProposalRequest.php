<?php

namespace App\Http\Requests;

use App\Models\Proposal;
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
            'images.*.path' => ['required_with:images', 'string', 'max:500'],
            'images.*.disk' => ['required_with:images', 'string', 'in:public'],
            'images.*.alt' => ['nullable', 'string', 'max:255'],
            'images.*.order' => ['nullable', 'integer', 'min:0'],
            'category_id' => ['required', 'uuid', 'exists:categories,id'],
        ];
    }
}
