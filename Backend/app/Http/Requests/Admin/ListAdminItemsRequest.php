<?php

namespace App\Http\Requests\Admin;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Gestiona y valida las peticiones de listado de ítems por parte de administradores.
 */
class ListAdminItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Item::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::in([Item::STATUS_ACTIVE, Item::STATUS_INACTIVE])],
            'category_id' => ['sometimes', 'uuid', 'exists:categories,id'],
            'creator_id' => ['sometimes', 'uuid', 'exists:users,id'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['sometimes', Rule::in(['name', 'status', 'created_at'])],
            'sort_direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
/**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'El valor seleccionado para estado no es válido.',
            'category_id.uuid' => 'El campo categoría debe ser un identificador válido.',
            'category_id.exists' => 'El categoría seleccionado no es válido o no existe.',
            'creator_id.uuid' => 'El campo creador debe ser un identificador válido.',
            'creator_id.exists' => 'El creador seleccionado no es válido o no existe.',
            'search.string' => 'El campo búsqueda debe ser texto.',
            'search.max' => 'El campo búsqueda no puede superar los 255 caracteres/elementos.',
            'per_page.integer' => 'El campo resultados por página debe ser un número entero.',
            'per_page.min' => 'El campo resultados por página debe tener al menos 1 caracteres/elementos.',
            'per_page.max' => 'El campo resultados por página no puede superar los 100 caracteres/elementos.',
            'sort_by.in' => 'El valor seleccionado para ordenar por no es válido.',
            'sort_direction.in' => 'El valor seleccionado para dirección de orden no es válido.',
        ];
    }
}
