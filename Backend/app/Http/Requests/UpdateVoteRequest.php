<?php

namespace App\Http\Requests;

use App\Models\Vote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('vote')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::in([Vote::TYPE_VOTE, Vote::TYPE_SKIP])],
            'score' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:10'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->has('type') && !$this->has('score')) {
                $validator->errors()->add('type', 'Debes enviar al menos type o score para actualizar el voto.');
                return;
            }

            $currentVote = $this->route('vote');
            $type = $this->input('type', $currentVote?->type);
            $score = $this->has('score') ? $this->input('score') : $currentVote?->score;

            if ($type === Vote::TYPE_VOTE && $score === null) {
                $validator->errors()->add('score', 'No se ha registrado puntuacion asociada al voto.');
            }

            if ($type === Vote::TYPE_SKIP && $this->has('score') && $this->input('score') !== null) {
                $validator->errors()->add('score', 'No se ha registrado la puntuacion: votacion omitida.');
            }
        });
    }
}
