<?php

namespace App\Http\Requests\Lists;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida que se añada un ítem a una lista editorial.
 */
class AddEditorialListItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('list')) ?? false;
    }

    public function rules(): array
    {
        return [
            'item_id' => [
                'required',
                'uuid',
                Rule::exists('items', 'id')->whereNull('deleted_at'),
                Rule::unique('list_items', 'item_id')->where('list_id', $this->route('list')->id),
            ],
            'note' => ['nullable', 'string', 'max:280'],
        ];
    }

    /**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'item_id.required' => 'El ítem es obligatorio.',
            'item_id.uuid' => 'El ítem debe ser un identificador válido.',
            'item_id.exists' => 'El ítem no existe.',
            'item_id.unique' => 'El ítem ya está en la lista.',
            'note.string' => 'La nota debe ser texto.',
            'note.max' => 'La nota no puede superar los 280 caracteres.',
        ];
    }
}
