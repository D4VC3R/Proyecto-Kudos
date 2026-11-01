<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gestiona y valida las peticiones de listado de la cola de reportes por parte de administradores (F3-04).
 */
class ListAdminReportsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:pending,resolved,dismissed'],
            'reportable_type' => ['sometimes', 'string', 'in:comment,list,user'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'El valor seleccionado para estado no es válido.',
            'status.string' => 'El valor seleccionado para estado no es válido.',
            'reportable_type.in' => 'El tipo de contenido seleccionado no es válido.',
            'reportable_type.string' => 'El tipo de contenido seleccionado no es válido.',
            'per_page.integer' => 'El campo resultados por página debe ser un número entero.',
            'per_page.min' => 'Pide al menos 1 reporte por página.',
            'per_page.max' => 'No puedes pedir más de 100 reportes por página.',
            'page.integer' => 'La página no es válida.',
            'page.min' => 'La página no es válida.',
        ];
    }
}
