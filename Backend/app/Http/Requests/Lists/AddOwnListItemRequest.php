<?php

namespace App\Http\Requests\Lists;

use App\Models\Item;
use Illuminate\Validation\Rule;

/**
 * Valida que se añada un ítem a una lista propia (F3-01): solo ítems activos y sin repetir.
 */
class AddOwnListItemRequest extends AddEditorialListItemRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', $this->route('list')) ?? false;
    }

    public function rules(): array
    {
        return [
            'item_id' => [
                'required',
                'uuid',
                Rule::exists('items', 'id')->whereNull('deleted_at')->where('status', Item::STATUS_ACTIVE),
                Rule::unique('list_items', 'item_id')->where('list_id', $this->route('list')->id),
            ],
            'note' => ['nullable', 'string', 'max:280'],
        ];
    }
}
