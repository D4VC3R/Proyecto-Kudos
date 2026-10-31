<?php

namespace App\Http\Requests\Lists;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida el borrado de una lista editorial.
 */
class DeleteEditorialListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete', $this->route('list')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
