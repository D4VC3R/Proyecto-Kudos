<?php

namespace App\Http\Requests\Admin;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Gestiona y valida las peticiones de moderación de ítems por parte de administradores.
 */
class ModerateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('item')) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:' . Item::STATUS_ACTIVE . ',' . Item::STATUS_INACTIVE],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** Agrega una validación personalizada para asegurar que se proporcione un motivo al desactivar un ítem. */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (
                $this->input('status') === Item::STATUS_INACTIVE
                && blank($this->input('reason'))
            ) {
                $validator->errors()->add('reason', 'El motivo es obligatorio al desactivar un item.');
            }
        });
    }
}

