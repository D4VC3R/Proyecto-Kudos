<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVoteRequest extends FormRequest
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
		    // Obligatorio, debe ser un UUID válido, y debe existir en la tabla items
		    'item_id' => ['required', 'uuid', 'exists:items,id'],

		    // Obligatorio, entero, entre 0 y 10 (según tu PDF de propuesta)
		    'score' => ['required', 'integer', 'min:0', 'max:10'],
	    ];
    }
}
