<?php

namespace App\Http\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gestiona y valida las peticiones de actualización de comentarios de ítems por parte de usuarios.
 */
class UpdateItemCommentRequest extends FormRequest
{
    /** Verifica que el usuario tenga permiso para actualizar el comentario específico.
     * - El permiso se verifica utilizando la política de autorización 'update' para el modelo de comentario.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('comment')) ?? false;
    }

    /** Define las reglas de validación para actualizar un comentario de ítem.
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:2', 'max:2000'],
        ];
    }
/**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'El campo contenido es obligatorio.',
            'content.string' => 'El campo contenido debe ser texto.',
            'content.min' => 'El campo contenido debe tener al menos 2 caracteres/elementos.',
            'content.max' => 'El campo contenido no puede superar los 2000 caracteres/elementos.',
        ];
    }
}
