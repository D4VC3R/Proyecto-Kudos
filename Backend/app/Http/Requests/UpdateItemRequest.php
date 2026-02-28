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
			'name' => ['sometimes', 'required', 'string', 'max:255'],
			'description' => ['sometimes', 'required', 'string', 'min:20', 'max:2000'],
			'image' => ['sometimes', 'nullable', 'string', 'url', 'max:500'],
			'category_id' => ['sometimes', 'required', 'uuid', 'exists:categories,id'],
			'tag_ids' => ['sometimes', 'nullable', 'array', 'max:5'],
			'tag_ids.*' => ['uuid', 'exists:tags,id'],
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
			'image.url' => 'La imagen debe ser una URL válida.',
			'category_id.required' => 'Debes seleccionar una categoría.',
			'category_id.exists' => 'La categoría seleccionada no existe.',
			'tag_ids.max' => 'No puedes seleccionar más de 5 tags.',
			'tag_ids.*.exists' => 'Uno o más tags seleccionados no existen.',
		];
	}
}
