<?php

namespace App\Http\Requests\Proposals;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gestiona y valida las peticiones de eliminación de propuestas por parte de usuarios.
 */
class DeleteProposalRequest extends FormRequest
{
    /** Verifica que el usuario tenga permiso para eliminar la propuesta específica.
     * - El permiso se verifica utilizando la política de autorización 'delete' para el modelo de propuesta.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('delete', $this->route('proposal')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}

