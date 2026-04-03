<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteItemCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete', $this->route('comment')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}

