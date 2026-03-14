<?php

namespace App\Http\Requests;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('item')) ?? false;
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
            'tag_ids' => ['sometimes', 'nullable', 'array', 'max:5'],
            'tag_ids.*' => ['uuid', 'exists:tags,id'],
            'status' => ['sometimes', 'string', 'in:' . Item::STATUS_ACTIVE . ',' . Item::STATUS_INACTIVE],
            'moderation_reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}

