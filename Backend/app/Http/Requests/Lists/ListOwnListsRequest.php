<?php

namespace App\Http\Requests\Lists;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida el listado de las listas propias del usuario (F3-01). `item_id` opcional: marca las que ya lo contienen.
 */
class ListOwnListsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'item_id' => ['sometimes', 'uuid'],
        ];
    }

    /**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'item_id.uuid' => 'El ítem debe ser un identificador válido.',
        ];
    }
}
