<?php

namespace App\Http\Requests\Lists;

/**
 * Valida el reordenado de una lista propia (F3-01): mismas reglas que la editorial, otra autorización.
 */
class ReorderOwnListItemsRequest extends ReorderEditorialListItemsRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', $this->route('list')) ?? false;
    }
}
