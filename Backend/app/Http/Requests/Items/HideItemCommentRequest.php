<?php

namespace App\Http\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gestiona y valida las peticiones de ocultar comentarios de ítems por parte de usuarios.
 */
class HideItemCommentRequest extends FormRequest
{
    /** Verifica que el usuario tenga permiso para ocultar el comentario específico.
     * - El permiso se verifica utilizando la política de autorización 'hide' para el modelo de comentario.
     * - Si el usuario no tiene permiso, la petición será denegada automáticamente.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('hide', $this->route('comment')) ?? false;
    }

    /** Define las reglas de validación para ocultar un comentario.
     * - El campo 'reason' es opcional, pero si se proporciona, debe ser una cadena de texto con un máximo de 1000 caracteres.
     * - Este campo puede ser utilizado para que el usuario proporcione una razón para ocultar el comentario, lo cual puede ser útil para moderadores o administradores al revisar la acción.
     */
    public function rules(): array
    {
        return [
            'reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
/**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'reason.string' => 'El campo motivo debe ser texto.',
            'reason.max' => 'El campo motivo no puede superar los 1000 caracteres/elementos.',
        ];
    }
}
