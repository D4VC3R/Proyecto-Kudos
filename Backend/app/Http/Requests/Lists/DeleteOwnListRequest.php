<?php

namespace App\Http\Requests\Lists;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida el borrado de una lista propia (F3-01).
 */
class DeleteOwnListRequest extends FormRequest
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
