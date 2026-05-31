<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Gestiona y valida las peticiones de listado de usuarios por parte de administradores.
 */
class ListAdminUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /**
     * Define las reglas de validación para los parámetros de consulta utilizados en el listado de usuarios.
     * Permite filtrar por búsqueda, estado de baneo, rol, paginación y ordenamiento.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_banned' => ['sometimes', 'boolean'],
            'ban_state' => ['sometimes', Rule::in(['temporary', 'permanent', 'expired', 'active'])],
            'role' => ['sometimes', 'string', 'max:100'],
            'is_verified' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['sometimes', Rule::in(['name', 'email', 'role', 'status', 'created_at'])],
            'sort_direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
/**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'search.string' => 'El campo búsqueda debe ser texto.',
            'search.max' => 'El campo búsqueda no puede superar los 255 caracteres/elementos.',
            'is_banned.boolean' => 'El campo está baneado debe ser verdadero o falso.',
            'ban_state.in' => 'El valor seleccionado para estado de baneo no es válido.',
            'role.string' => 'El campo rol debe ser texto.',
            'role.max' => 'El campo rol no puede superar los 100 caracteres/elementos.',
            'is_verified.boolean' => 'El campo verificado debe ser verdadero o falso.',
            'per_page.integer' => 'El campo resultados por página debe ser un número entero.',
            'per_page.min' => 'El campo resultados por página debe tener al menos 1 caracteres/elementos.',
            'per_page.max' => 'El campo resultados por página no puede superar los 100 caracteres/elementos.',
            'sort_by.in' => 'El valor seleccionado para ordenar por no es válido.',
            'sort_direction.in' => 'El valor seleccionado para dirección de orden no es válido.',
        ];
    }
}
