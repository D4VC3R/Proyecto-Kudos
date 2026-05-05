<?php

namespace App\Http\Requests\Admin;

use App\Models\Item;
use App\Services\CategoryExtraDataValidator;
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
            'extra_data' => ['sometimes', 'nullable', 'array'],
            'category_id' => ['sometimes', 'required', 'uuid', 'exists:categories,id'],
            'status' => ['sometimes', 'string', 'in:' . Item::STATUS_ACTIVE . ',' . Item::STATUS_INACTIVE],
            'moderation_reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var Item|null $item */
            $item = $this->route('item');
            $categoryId = $this->input('category_id', $item?->category_id);

            if (!is_string($categoryId) || $categoryId === '') {
                return;
            }

            $extraDataInput = $this->input('extra_data');
            if ($extraDataInput !== null && !is_array($extraDataInput)) {
                $validator->errors()->add('extra_data', 'extra_data debe ser un objeto JSON.');
                return;
            }

            $existingExtraData = is_array($item?->extra_data) ? $item->extra_data : [];

            $errors = app(CategoryExtraDataValidator::class)->validate(
                categoryId: $categoryId,
                inputExtraData: $extraDataInput,
                existingExtraData: $existingExtraData,
                requireRequiredFields: true,
            );

            foreach ($errors as $field => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add($field, $message);
                }
            }
        });
    }
}

