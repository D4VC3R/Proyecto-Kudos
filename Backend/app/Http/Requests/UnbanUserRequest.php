<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnbanUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}

