<?php

namespace App\Http\Requests\Lists;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida que se puede quitar un ítem de una lista propia (F3-01).
 */
class RemoveOwnListItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', $this->route('list')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
