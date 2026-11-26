<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida el borrado de la propia cuenta (F3-08): exige la contraseña actual.
 */
class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // current_password con el guard de Sanctum (la API se autentica con token).
            'password' => ['required', 'string', 'current_password:sanctum'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Escribe tu contraseña para confirmar el borrado.',
            'password.current_password' => 'La contraseña no es correcta.',
        ];
    }
}
