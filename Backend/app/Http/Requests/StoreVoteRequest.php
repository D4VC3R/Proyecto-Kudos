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
        if (!$this->user()) {
            return false;
        }

        $itemId = $this->input('item_id');
        if (!is_string($itemId) || $itemId === '') {
            return true;
        }

        $item = Item::query()->select(['id', 'status'])->find($itemId);
        if (!$item) {
            return true;
        }

        return $this->user()->can('create', [Vote::class, $item]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'item_id' => ['required', 'uuid', Rule::exists('items', 'id')],
            'type' => ['required', Rule::in([Vote::TYPE_VOTE, Vote::TYPE_SKIP])],
            'score' => [
                'nullable',
                'integer',
                'min:0',
                'max:10',
                Rule::requiredIf(fn () => $this->input('type') === Vote::TYPE_VOTE),
                Rule::prohibitedIf(fn () => $this->input('type') === Vote::TYPE_SKIP),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'score.required' => 'No se ha registrado puntuacion asociada al voto.',
            'score.prohibited' => 'No se ha registrado la puntuación: votación omitida.',
        ];
    }
}
