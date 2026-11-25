<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gestiona y valida las peticiones de desbaneos de usuarios por parte de administradores.
 */
class UnbanUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('unban', $this->route('user')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}

