<?php

namespace App\Http\Requests\Notifications;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gestiona y valida las peticiones de listado de notificaciones del usuario autenticado.
 */
class ListNotificationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'per_page.integer' => 'El campo resultados por página debe ser un número entero.',
            'per_page.min' => 'Pide al menos 1 notificación por página.',
            'per_page.max' => 'No puedes pedir más de 50 notificaciones por página.',
            'page.integer' => 'La página no es válida.',
            'page.min' => 'La página no es válida.',
        ];
    }
}
