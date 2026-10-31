<?php

namespace App\Http\Requests\Lists;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la consulta de una lista editorial en el panel de administración.
 */
class ShowEditorialListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('list')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
