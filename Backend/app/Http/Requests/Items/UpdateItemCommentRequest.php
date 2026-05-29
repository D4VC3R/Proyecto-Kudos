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
}

