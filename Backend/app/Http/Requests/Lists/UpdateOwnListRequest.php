<?php

namespace App\Http\Requests\Lists;

use App\Models\UserList;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Valida la edición de una lista propia (F3-01). El slug no cambia. Una lista ocultada por moderación
 * solo puede seguir siendo privada.
 */
class UpdateOwnListRequest extends UpdateEditorialListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', $this->route('list')) ?? false;
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            'visibility' => ['sometimes', Rule::in([
                UserList::VISIBILITY_PUBLIC,
                UserList::VISIBILITY_UNLISTED,
                UserList::VISIBILITY_PRIVATE,
            ])],
        ];
    }

    /**
     * Impide volver a publicar una lista que la moderación ocultó.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var UserList $list */
            $list = $this->route('list');
            if ($list->isHiddenByModeration()
                && $this->has('visibility')
                && $this->input('visibility') !== UserList::VISIBILITY_PRIVATE) {
                $validator->errors()->add('visibility', 'Esta lista se ocultó por moderación y no puede volver a ser pública ni no listada.');
            }
        });
    }

    /**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'visibility.in' => 'La visibilidad debe ser pública, no listada o privada.',
        ];
    }
}
