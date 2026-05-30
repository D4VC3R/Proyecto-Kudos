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
     * - El campo 'search' es opcional, pero si se proporciona, debe ser una cadena con un máximo de 255 caracteres.
     * - El campo 'sort_by' es opcional, pero si se proporciona, debe ser una cadena que coincida con uno de los valores permitidos: 'vote_avg', 'recent', 'name', o 'random'.
     * - El campo 'sort_order' es opcional, pero si se proporciona, debe ser una cadena que coincida con uno de los valores permitidos: 'asc' o 'desc'.
     * - El campo 'per_page' es opcional, pero si se proporciona, debe ser un entero entre 1 y 100.
     */
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'string', 'exists:categories,id'],
	          'category_slug' => ['nullable', 'string', 'exists:categories,slug'],
            'search' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', 'string', 'in:vote_avg,recent,name,random'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
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
            'search.max' => 'El campo búsqueda no puede superar los 255 caracteres/elementos.',
            'sort_by.string' => 'El campo ordenar por debe ser texto.',
            'sort_by.in' => 'El valor seleccionado para ordenar por no es válido.',
            'sort_order.string' => 'El campo dirección de orden debe ser texto.',
            'sort_order.in' => 'El valor seleccionado para dirección de orden no es válido.',
            'per_page.integer' => 'El campo resultados por página debe ser un número entero.',
            'per_page.min' => 'El campo resultados por página debe tener al menos 1 caracteres/elementos.',
            'per_page.max' => 'El campo resultados por página no puede superar los 100 caracteres/elementos.',
        ];
    }
}
