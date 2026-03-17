<?php

namespace App\Http\Requests;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAdminItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::in([Item::STATUS_ACTIVE, Item::STATUS_INACTIVE])],
            'category_id' => ['sometimes', 'uuid', 'exists:categories,id'],
            'creator_id' => ['sometimes', 'uuid', 'exists:users,id'],
            'search' => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}

