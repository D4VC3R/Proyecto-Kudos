<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetNextCategoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->route('category')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}

