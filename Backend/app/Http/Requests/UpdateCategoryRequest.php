<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
		public function rules(): array
		{
			$categoryId = $this->route('category')->id;

			return [
				'name' => [
					'sometimes',
					'required',
					'string',
					'max:255',
					Rule::unique('categories', 'name')->ignore($categoryId),
				],
				'description' => ['sometimes', 'required', 'string', 'max:1000'],
				'slug' => [
					'sometimes',
					'required',
					'string',
					'max:255',
					'alpha_dash',
					Rule::unique('categories', 'slug')->ignore($categoryId),
				],
				'image' => ['sometimes', 'nullable', 'string', 'url', 'max:500'],
			];
		}
		public function messages(): array
		{
			return [
				'name.required' => 'El nombre de la categoría es obligatorio.',
				'name.unique' => 'Ya existe una categoría con ese nombre.',
				'name.max' => 'El nombre no puede exceder 255 caracteres.',
				'description.required' => 'La descripción es obligatoria.',
				'description.max' => 'La descripción no puede exceder 1000 caracteres.',
				'slug.required' => 'El slug es obligatorio.',
				'slug.unique' => 'Ya existe una categoría con ese slug.',
				'slug.alpha_dash' => 'El slug solo puede contener letras, números, guiones y guiones bajos.',
				'image.url' => 'La imagen debe ser una URL válida.',
			];
		}

		protected function prepareForValidation(): void
		{
			// Si se actualiza el nombre pero no el slug, regenerar el slug
			if ($this->has('name') && !$this->has('slug')) {
				$this->merge([
					'slug' => Str::slug($this->name),
				]);
			}
		}
}
