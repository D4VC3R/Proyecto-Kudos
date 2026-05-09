<?php

namespace App\Http\Requests\Admin;

use App\Models\Proposal;
use App\Services\CategoryExtraDataValidator;
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
            'extra_data' => ['nullable', 'array'],
            'category_id' => ['required', 'uuid', 'exists:categories,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $categoryId = $this->input('category_id');
            if (!is_string($categoryId) || $categoryId === '') {
                return;
            }

            $extraData = $this->input('extra_data');
            if ($extraData !== null && !is_array($extraData)) {
                $validator->errors()->add('extra_data', 'extra_data debe ser un objeto JSON.');
                return;
            }

            $errors = app(CategoryExtraDataValidator::class)->validate(
                categoryId: $categoryId,
                inputExtraData: $extraData,
                existingExtraData: [],
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
