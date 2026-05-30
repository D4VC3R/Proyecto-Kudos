<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gestiona y valida las peticiones de listado de ranking de usuarios por parte de usuarios.
 */
class ListUserRankingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
/**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'page.integer' => 'El campo página debe ser un número entero.',
            'page.min' => 'El campo página debe tener al menos 1 caracteres/elementos.',
        ];
    }
}
