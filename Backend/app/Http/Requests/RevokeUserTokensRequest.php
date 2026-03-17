<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RevokeUserTokensRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $admin = $this->user();
            $target = $this->route('user');

            if ($admin && $target && $admin->id === $target->id) {
                $validator->errors()->add('user', 'No puedes revocar tu propia sesión desde esta acción.');
            }
        });
    }
}

