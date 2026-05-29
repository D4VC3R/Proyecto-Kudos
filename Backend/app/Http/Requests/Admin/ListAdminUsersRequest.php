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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['sometimes', Rule::in(['name', 'email', 'role', 'status', 'created_at'])],
            'sort_direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
}

