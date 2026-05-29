<?php

namespace App\Http\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gestiona y valida las peticiones de desocultación de comentarios de ítems por parte de usuarios.
 */
class UnhideItemCommentRequest extends FormRequest
{
    /** Verifica que el usuario tenga permiso para desocultar el comentario específico.
     * - El permiso se verifica utilizando la política de autorización 'unhide' para el modelo de comentario.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('unhide', $this->route('comment')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}

