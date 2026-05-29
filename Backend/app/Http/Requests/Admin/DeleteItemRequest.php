<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gestiona y valida las peticiones de eliminación de ítems por parte de administradores.
 */
class DeleteItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete', $this->route('item')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}

