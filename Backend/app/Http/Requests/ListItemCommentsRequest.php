<?php

namespace App\Http\Requests;

use App\Models\Item;
use App\Models\ItemComment;
use Illuminate\Foundation\Http\FormRequest;

class ListItemCommentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Item|null $item */
        $item = $this->route('item');
        if (!$item) {
            return false;
        }

        $user = $this->user();

        return $user?->can('viewAny', [ItemComment::class, $item])
            ?? ($item->status === Item::STATUS_ACTIVE);
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}

