<?php

namespace App\Http\Requests\Votes;

use Illuminate\Foundation\Http\FormRequest;

class DeleteVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete', $this->route('vote')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}

