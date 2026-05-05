<?php

namespace App\Http\Requests\Proposals;

use Illuminate\Foundation\Http\FormRequest;

class ShowProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->route('proposal')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}

