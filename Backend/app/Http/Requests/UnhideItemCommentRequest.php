<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnhideItemCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('unhide', $this->route('comment')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}

