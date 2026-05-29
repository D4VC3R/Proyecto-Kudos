<?php

namespace App\Http\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gestiona y valida las peticiones de eliminación de comentarios de ítems por parte de usuarios.
 */
class DeleteItemCommentRequest extends FormRequest
{
    /** Verifica que el usuario tenga permiso para eliminar el comentario específico.
     * - El permiso se verifica utilizando la política de autorización 'delete' para el modelo de comentario.
     * - Si el usuario no tiene permiso, la petición será denegada automáticamente.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('delete', $this->route('comment')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}

