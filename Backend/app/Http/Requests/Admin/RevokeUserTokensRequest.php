<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gestiona y valida las peticiones de revocación de tokens de usuario por parte de administradores.
 */
class RevokeUserTokensRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [];
    }

    /** Evita que un administrador revoque sus propios tokens de sesión.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $admin = $this->user();
            $target = $this->route('user');

            if ($admin && $target && $admin->id === $target->id) {
                $validator->errors()->add('user', 'No puedes revocar tu propia sesión desde esta acción.');
            }
        });
    }
}

