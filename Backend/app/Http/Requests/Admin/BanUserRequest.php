<?php

namespace App\Http\Requests\Admin;

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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $admin = $this->user();
            $target = $this->route('user');

            if ($admin && $target && $admin->id === $target->id) {
                $validator->errors()->add('user', 'No puedes banear tu propia cuenta.');
            }
        });
    }
}


