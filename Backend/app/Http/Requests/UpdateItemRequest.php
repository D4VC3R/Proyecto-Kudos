<?php

namespace App\Http\Requests;

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
            'id' => ['sometimes','required', 'uuid', 'exists:items,id'],
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
		];
	}

	public function messages(): array
	{
		return [
            'id.required' => 'El item seleccionado no existe.',
			'name.required' => 'El nombre del item es obligatorio.',
			'name.max' => 'El nombre no puede exceder 255 caracteres.',
			'description.required' => 'La descripción es obligatoria.',
			'description.min' => 'La descripción debe tener al menos 20 caracteres.',
			'description.max' => 'La descripción no puede exceder 2000 caracteres.',
            'images.*.path.required' => 'La imagen es obligatoria.',
			'category_id.required' => 'Debes seleccionar una categoría.',
			'category_id.exists' => 'La categoría seleccionada no existe.',
			'tag_ids.max' => 'No puedes seleccionar más de 5 tags.',
			'tag_ids.*.exists' => 'Uno o más tags seleccionados no existen.',
		];
	}
}
