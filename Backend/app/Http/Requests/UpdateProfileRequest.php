<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
	    return [
		    'avatar' => ['nullable', 'string', 'url', 'max:255'],
		    'biography' => ['nullable', 'string', 'max:500'],
		    'social_links' => ['nullable', 'array'],
		    'city' => ['nullable', 'string', 'max:100'],
		    'birthdate' => ['nullable', 'date', 'before:today'],
	    ];
    }
}
