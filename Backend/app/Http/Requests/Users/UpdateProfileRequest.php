<?php

namespace App\Http\Requests\Users;

use App\Rules\FileOrUrlRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Gestiona y valida las peticiones de actualización de perfil por parte de usuarios.
 * Permite actualizar el avatar, biografía, enlaces sociales, ciudad, fecha de nacimiento e intereses (categorías del onboarding, F2-07).
 * Se incluyen validaciones específicas para cada campo, como el formato de URL para los enlaces sociales y restricciones de edad para la fecha de nacimiento.
 */
class UpdateProfileRequest extends FormRequest
{
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
			// Slugs de categorías activas (onboarding, F2-07). Lista vacía permitida.
			'interests' => ['sometimes', 'array', 'max:20'],
			'interests.*' => ['required', 'string', 'distinct', Rule::exists('categories', 'slug')->where('is_active', true)],
		];
	}

	public function messages(): array
	{
		return [
			'social_links.max' => 'No puedes añadir más de 5 enlaces sociales.',
			'social_links.*.url' => 'Cada enlace social debe ser una URL válida.',
			'birthdate.before' => 'Debes tener al menos 13 años para registrarte.',
			'birthdate.after' => 'La fecha de nacimiento introducida no es válida.',
			'interests.array' => 'Los intereses deben ser una lista de categorías.',
			'interests.max' => 'No puedes elegir más de 20 categorías.',
			'interests.*.distinct' => 'Has repetido una categoría.',
			'interests.*.exists' => 'Alguna de las categorías elegidas no existe o no está disponible.',
		];
	}
}
