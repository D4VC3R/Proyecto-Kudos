<?php

namespace App\Http\Requests\Proposals;

use App\Models\Proposal;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Gestiona y valida las peticiones de listado de propuestas pendientes por parte de usuarios.
 * - Solo los usuarios con permiso para revisar cualquier propuesta (admins) pueden acceder a esta funcionalidad.
 * - Permite validar parámetros opcionales como 'per_page' para controlar la paginación de resultados.
 */
class ListPendingProposalsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reviewAny', Proposal::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}

