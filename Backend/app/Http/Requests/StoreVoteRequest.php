<?php

namespace App\Http\Requests;

use App\Models\Item;
use App\Models\Vote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $item = Item::find($this->input('item_id'));
        return $item && $this->user()?->can('create', [Vote::class, $item]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
	    return [
		    // Obligatorio, debe ser un UUID válido, debe existir en la tabla items y su estado debe ser 'active'
            'item_id' => ['required', 'uuid', Rule::exists('items', 'id')->where(fn ($q) => $q->where('status', Item::STATUS_ACTIVE))],

		    // Obligatorio, entero, entre 0 y 10
		    'score' => ['required', 'integer', 'min:0', 'max:10'],
	    ];
    }
}
