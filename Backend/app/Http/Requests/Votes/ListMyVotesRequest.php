<?php

namespace App\Http\Requests\Votes;

use App\Models\Vote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Gestiona y valida las peticiones de listado de votos del usuario autenticado.
 */
class ListMyVotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::in([Vote::TYPE_VOTE, Vote::TYPE_SKIP])],
            'category_slug' => ['sometimes', 'nullable', 'string', 'exists:categories,slug'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
/**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'type.in' => 'El valor seleccionado para tipo no es válido.',
            'category_slug.string' => 'El campo categoría debe ser texto.',
            'category_slug.exists' => 'El categoría seleccionado no es válido o no existe.',
            'search.string' => 'El campo búsqueda debe ser texto.',
            'search.max' => 'El campo búsqueda no puede superar los 255 caracteres/elementos.',
            'per_page.integer' => 'El campo resultados por página debe ser un número entero.',
            'per_page.min' => 'El campo resultados por página debe tener al menos 1 caracteres/elementos.',
            'per_page.max' => 'El campo resultados por página no puede superar los 100 caracteres/elementos.',
        ];
    }
}
