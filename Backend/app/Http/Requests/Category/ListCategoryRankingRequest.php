<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la paginación del ranking público de una categoría.
 * per_page va acotado para que una petición anónima no pueda pedir la categoría entera de una vez.
 */
class ListCategoryRankingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.integer' => 'El número de resultados por página debe ser un número entero.',
            'per_page.min' => 'El número de resultados por página debe ser al menos 1.',
            'per_page.max' => 'El número de resultados por página no puede ser mayor que 100.',
        ];
    }
}
