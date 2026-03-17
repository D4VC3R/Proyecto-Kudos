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
        $itemId = $this->input('item_id');
        if (!is_string($itemId) || $itemId === '') {
            return false;
        }

        $item = Item::query()->select(['id', 'status'])->find($itemId);

        return $item && ($this->user()?->can('create', [Vote::class, $item]) ?? false);
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
            'score' => ['nullable', 'integer', 'min:0', 'max:10'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');
            $score = $this->input('score');

            if ($type === Vote::TYPE_VOTE && $score === null) {
                $validator->errors()->add('score', 'No se ha registrado puntuacion asociada al voto.');
            }

            if ($type === Vote::TYPE_SKIP && $score !== null) {
                $validator->errors()->add('score', 'No se ha registrado la puntuación: votación omitida.');
            }
        });
    }
}
