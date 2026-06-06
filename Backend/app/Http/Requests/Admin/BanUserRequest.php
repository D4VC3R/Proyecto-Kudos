<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gestiona y valida las peticiones de baneo de usuarios por parte de administradores.
 */
class BanUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'is_permanent' => ['sometimes', 'boolean'],
            'days' => ['required_unless:is_permanent,true', 'nullable', 'integer', 'min:1', 'max:3650'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $admin = $this->user();
            $target = $this->route('user');

            if ($admin && $target && $admin->id === $target->id) {
                $validator->errors()->add('user', 'No puedes banear tu propia cuenta.');
            }
        });
    }
/**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'is_permanent.boolean' => 'El campo es permanente debe ser verdadero o falso.',
            'days.required_unless' => 'El campo días es obligatorio.',
            'days.integer' => 'El campo días debe ser un número entero.',
            'days.min' => 'El campo días requiere al menos un dígito.',
            'days.max' => 'El campo días no puede superar los 3650 caracteres/elementos.',
            'reason.required' => 'El campo motivo es obligatorio.',
            'reason.string' => 'El campo motivo debe ser texto.',
            'reason.max' => 'El campo motivo no puede superar los 1000 caracteres/elementos.',
        ];
    }
}
