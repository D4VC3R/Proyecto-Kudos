<?php

namespace App\Http\Requests\Admin;

use App\Models\Proposal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Gestiona y valida las peticiones de listado de propuestas por parte de administradores.
 */
class ListAdminProposalsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reviewAny', Proposal::class) ?? false;
    }

    /**
     * Define las reglas de validación para los parámetros de consulta al listar propuestas.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::in([
                Proposal::STATUS_PENDING,
                Proposal::STATUS_ACCEPTED,
                Proposal::STATUS_REJECTED,
                Proposal::STATUS_CHANGES_REQUESTED,
            ])],
            'creator_id' => ['sometimes', 'uuid', 'exists:users,id'],
            'reviewed_by' => ['sometimes', 'nullable', 'uuid', 'exists:users,id'],
            'category_id' => ['sometimes', 'uuid', 'exists:categories,id'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}

