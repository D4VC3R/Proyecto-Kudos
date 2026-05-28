<?php

namespace App\Http\Requests\Items;

use App\Models\ItemComment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ListItemCommentsRequest extends FormRequest
{
    public function authorize(): bool
    {
	    $item = $this->route('item');
	    if (!$item) {
		    return false;
	    }

	    return Gate::allows('viewAny', [ItemComment::class, $item]);
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}

