<?php

namespace App\Http\Requests\Lists;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la edición de la nota de una entrada de una lista propia (F3-01). `null` o vacía la borra.
 */
class UpdateOwnListItemNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', $this->route('list')) ?? false;
    }

    public function rules(): array
    {
        return [
            'note' => ['present', 'nullable', 'string', 'max:280'],
        ];
    }

    /**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'note.present' => 'La nota es obligatoria (puede ir vacía).',
            'note.string' => 'La nota debe ser texto.',
            'note.max' => 'La nota no puede superar los 280 caracteres.',
        ];
    }
}
