<?php

namespace App\Http\Requests\Votes;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gestiona y valida las peticiones de eliminación de votos por parte de usuarios.
 * Permite a los usuarios eliminar sus votos existentes.
 */
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

