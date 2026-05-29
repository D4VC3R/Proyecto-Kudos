<?php

namespace App\Http\Requests\Admin;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Gestiona y valida las peticiones de listado de ítems por parte de administradores.
 */
class ListAdminItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Item::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::in([Item::STATUS_ACTIVE, Item::STATUS_INACTIVE])],
            'category_id' => ['sometimes', 'uuid', 'exists:categories,id'],
            'creator_id' => ['sometimes', 'uuid', 'exists:users,id'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['sometimes', Rule::in(['name', 'status', 'created_at'])],
            'sort_direction' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
}

