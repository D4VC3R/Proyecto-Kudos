<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HideItemCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('hide', $this->route('comment')) ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}

