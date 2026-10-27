<?php

namespace App\Http\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gestiona y valida las peticiones de listado de ítems por parte de usuarios.
 */
class ListItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Define las reglas de validación para listar los ítems.
     * - El campo 'category_id' es opcional, pero si se proporciona, debe ser una cadena que exista en la columna 'id' de la tabla 'categories'.
     * - El campo 'category_slug' es opcional, pero si se proporciona, debe ser una cadena que exista en la columna 'slug' de la tabla 'categories'.
     * - El campo 'search' es opcional, pero si se proporciona, debe ser una cadena de entre 2 y 255 caracteres (F0-09: la búsqueda global y Explorar no envían búsquedas de 1 carácter).
     * - El campo 'sort_by' es opcional, pero si se proporciona, debe ser una cadena que coincida con uno de los valores permitidos: 'vote_avg', 'recent', 'name', o 'random'.
     * - El campo 'sort_order' es opcional, pero si se proporciona, debe ser una cadena que coincida con uno de los valores permitidos: 'asc' o 'desc'.
     * - El campo 'per_page' es opcional, pero si se proporciona, debe ser un entero entre 1 y 100.
     * - El campo 'discoverable' es opcional; si es verdadero, excluye los ítems con quality_score por debajo del umbral (F0-05).
     */
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'string', 'exists:categories,id'],
	          'category_slug' => ['nullable', 'string', 'exists:categories,slug'],
            'search' => ['nullable', 'string', 'min:2', 'max:255'],
            'sort_by' => ['nullable', 'string', 'in:vote_avg,recent,name,random'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'discoverable' => ['nullable', 'boolean'],
        ];
    }
/**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'category_id.string' => 'El campo categoría debe ser texto.',
            'category_id.exists' => 'El categoría seleccionado no es válido o no existe.',
            'category_slug.string' => 'El campo categoría debe ser texto.',
            'category_slug.exists' => 'El categoría seleccionado no es válido o no existe.',
            'search.string' => 'El campo búsqueda debe ser texto.',
            'search.min' => 'La búsqueda debe tener al menos 2 caracteres.',
            'search.max' => 'El campo búsqueda no puede superar los 255 caracteres/elementos.',
            'sort_by.string' => 'El campo ordenar por debe ser texto.',
            'sort_by.in' => 'El valor seleccionado para ordenar por no es válido.',
            'sort_order.string' => 'El campo dirección de orden debe ser texto.',
            'sort_order.in' => 'El valor seleccionado para dirección de orden no es válido.',
            'per_page.integer' => 'El campo resultados por página debe ser un número entero.',
            'per_page.min' => 'El campo resultados por página debe tener al menos 1 caracteres/elementos.',
            'per_page.max' => 'El campo resultados por página no puede superar los 100 caracteres/elementos.',
            'discoverable.boolean' => 'El campo descubrible debe ser verdadero o falso.',
        ];
    }
}
