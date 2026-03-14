<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BanUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'is_permanent' => ['sometimes', 'boolean'],
            'days' => ['required_unless:is_permanent,true', 'nullable', 'integer', 'min:1', 'max:3650'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}


