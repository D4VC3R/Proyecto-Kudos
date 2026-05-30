<?php

namespace App\Http\Requests\Items;

use App\Models\ItemComment;
use Illuminate\Foundation\Http\FormRequest;

class StoreItemCommentRequest extends FormRequest
{
    /** Verifica que el usuario tenga permiso para crear un comentario en el ítem específico.
     * - El permiso se verifica utilizando la política de autorización 'create' para el modelo de comentario, pasando el ítem como contexto.
     * - Si el usuario no tiene permiso, la petición será denegada automáticamente.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', [ItemComment::class, $this->route('item')]) ?? false;
    }

    /** Define las reglas de validación para crear un comentario en un ítem.
     * - El campo 'content' es obligatorio y debe tener una longitud mínima de 2 caracteres, (máxima de 2000).
     * - Estas reglas aseguran que el contenido del comentario sea adecuado y no demasiado corto ni excesivamente largo.
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
