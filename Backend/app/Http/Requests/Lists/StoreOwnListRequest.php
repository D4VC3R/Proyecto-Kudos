<?php

namespace App\Http\Requests\Lists;

use App\Models\UserList;
use Illuminate\Validation\Rule;

/**
 * Valida la creación de una lista propia (F3-01). Reutiliza las reglas de la editorial y añade la visibilidad.
 * No acepta is_editorial, slug, user_id ni likes_count: los fija el servidor.
 */
class StoreOwnListRequest extends StoreEditorialListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createOwn', UserList::class) ?? false;
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
