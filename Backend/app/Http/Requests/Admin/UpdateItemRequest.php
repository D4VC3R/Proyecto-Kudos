<?php

namespace App\Http\Requests\Admin;

use App\Models\Item;
use App\Services\CategoryExtraDataValidator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
		public function authorize(): bool
		{
			return $this->user()->can('update', $this->route('item'));
		}

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
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
		];
	}

	public function messages(): array
	{
		return [
			'name.required' => 'El nombre del item es obligatorio.',
			'name.max' => 'El nombre no puede exceder 255 caracteres.',
			'description.required' => 'La descripción es obligatoria.',
			'description.min' => 'La descripción debe tener al menos 20 caracteres.',
			'description.max' => 'La descripción no puede exceder 2000 caracteres.',
            'images.*.path.required' => 'La imagen es obligatoria.',
			'category_id.required' => 'Debes seleccionar una categoría.',
			'category_id.exists' => 'La categoría seleccionada no existe.',
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
