<?php

namespace App\Http\Requests\Lists;

use App\Models\UserList;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida el listado de listas editoriales del panel de administración.
 */
class ListEditorialListsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', UserList::class) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
