<?php

namespace App\Http\Requests\Lists;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida la edición de una lista editorial. El slug no cambia al editar el título.
 */
class UpdateEditorialListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('list')) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'is_ranked' => ['sometimes', 'boolean'],
            'cover_item_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('items', 'id')->whereNull('deleted_at')],
        ];
    }

    /**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'title.string' => 'El título debe ser texto.',
            'title.max' => 'El título no puede superar los 120 caracteres.',
            'description.string' => 'La descripción debe ser texto.',
            'description.max' => 'La descripción no puede superar los 2000 caracteres.',
            'is_ranked.boolean' => 'El campo lista numerada debe ser verdadero o falso.',
            'cover_item_id.uuid' => 'La portada debe ser un identificador válido.',
            'cover_item_id.exists' => 'El ítem de portada no existe.',
        ];
    }
}
