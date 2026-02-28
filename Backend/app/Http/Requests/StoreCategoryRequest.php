<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // La autorización se maneja en el middleware EnsureUserIsAdmin
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
	        'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
	        'description' => ['required', 'string', 'max:1000'],
	        'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug', 'alpha_dash'],
	        'image' => ['nullable', 'string', 'url', 'max:500'],
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
				'slug.unique' => 'Ya existe una categoría con ese slug.',
				'slug.alpha_dash' => 'El slug solo puede contener letras, números, guiones y guiones bajos.',
				'image.url' => 'La imagen debe ser una URL válida.',
			];
		}
		protected function prepareForValidation(): void
		{
			// Si no se proporciona slug, generarlo automáticamente desde el nombre
			if (!$this->slug && $this->name) {
				$this->merge([
					'slug' => Str::slug($this->name),
				]);
			}
		}
}
