<?php

namespace App\Http\Requests\Proposals;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gestiona y valida las peticiones de visualización de propuestas por parte de usuarios.
 */
class ShowProposalRequest extends FormRequest
{
    /** Verifica que el usuario tenga permiso para ver la propuesta específica.
     * - El permiso se verifica utilizando la política de autorización 'view' para el modelo de propuesta.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->route('proposal')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}

