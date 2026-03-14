<?php

namespace App\Http\Requests;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;

class ModerateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('item')) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:' . Item::STATUS_ACTIVE . ',' . Item::STATUS_INACTIVE],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (
                $this->input('status') === Item::STATUS_INACTIVE
                && blank($this->input('reason'))
            ) {
                $validator->errors()->add('reason', 'El motivo es obligatorio al desactivar un item.');
            }
        });
    }
}

