<?php

namespace App\Http\Requests\Users;

use App\Rules\FileOrUrlRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
	/**
	 * Determine if the user is authorized to make this request.
	 */
	public function authorize(): bool
	{
		return $this->user() !== null;
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array<string, ValidationRule|array|string>
	 */
	public function rules(): array
	{
		return [
			'avatar' => ['sometimes', 'nullable', new FileOrUrlRule()],
			'biography' => ['nullable', 'string', 'max:500'],
			'social_links' => ['nullable', 'array', 'max:5'],
			'social_links.*' => ['required_with:social_links', 'url', 'max:255'],
			'city' => ['nullable', 'string', 'max:100'],
			'birthdate' => ['nullable', 'date', 'before:-13 years', 'after:1900-01-01'],
		];
	}

	public function messages(): array
	{
		return [
			'social_links.max' => 'No puedes añadir más de 5 enlaces sociales.',
			'social_links.*.url' => 'Cada enlace social debe ser una URL válida.',
			'birthdate.before' => 'Debes tener al menos 13 años para registrarte.',
			'birthdate.after' => 'La fecha de nacimiento introducida no es válida.',
		];
	}
}