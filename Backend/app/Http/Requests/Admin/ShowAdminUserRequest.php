<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gestiona y valida las peticiones de visualización de detalles de usuarios por parte de administradores.
 */
class ShowAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->route('user')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}

