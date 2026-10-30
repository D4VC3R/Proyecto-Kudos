<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida GET /api/onboarding/items (F2-07): las categorías llegan como slugs separados por comas
 * (?categories=peliculas,series) o como array, y tienen que existir y estar activas.
 */
class OnboardingItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $value = $this->input('categories');

        if (is_string($value)) {
            $this->merge([
                'categories' => array_values(array_unique(array_filter(
                    array_map('trim', explode(',', $value)),
                    fn ($slug) => $slug !== ''
                ))),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'categories' => ['required', 'array', 'min:1', 'max:20'],
            'categories.*' => ['required', 'string', Rule::exists('categories', 'slug')->where('is_active', true)],
        ];
    }

    public function messages(): array
    {
        return [
            'categories.required' => 'Elige al menos una categoría.',
            'categories.max' => 'No puedes elegir más de 20 categorías.',
            'categories.*.exists' => 'Alguna de las categorías elegidas no existe o no está disponible.',
        ];
    }
}
