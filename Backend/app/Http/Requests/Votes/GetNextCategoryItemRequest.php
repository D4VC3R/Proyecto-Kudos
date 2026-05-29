<?php

namespace App\Http\Requests\Votes;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gestiona y valida las peticiones de obtención del siguiente ítem de una categoría para votar.
 */
class GetNextCategoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->route('category')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}

