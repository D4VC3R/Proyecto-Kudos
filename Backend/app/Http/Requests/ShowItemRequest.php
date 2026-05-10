<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->route('item')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}


