<?php

namespace App\Http\Requests\Items;

use App\Models\ItemComment;
use Illuminate\Foundation\Http\FormRequest;

class StoreItemCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [ItemComment::class, $this->route('item')]) ?? false;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:2', 'max:2000'],
        ];
    }
}

