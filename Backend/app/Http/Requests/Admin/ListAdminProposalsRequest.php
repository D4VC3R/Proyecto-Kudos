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
/**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'El valor seleccionado para estado no es válido.',
            'creator_id.uuid' => 'El campo creador debe ser un identificador válido.',
            'creator_id.exists' => 'El creador seleccionado no es válido o no existe.',
            'reviewed_by.uuid' => 'El campo revisor debe ser un identificador válido.',
            'reviewed_by.exists' => 'El revisor seleccionado no es válido o no existe.',
            'category_id.uuid' => 'El campo categoría debe ser un identificador válido.',
            'category_id.exists' => 'El categoría seleccionado no es válido o no existe.',
            'search.string' => 'El campo búsqueda debe ser texto.',
            'search.max' => 'El campo búsqueda no puede superar los 255 caracteres/elementos.',
            'per_page.integer' => 'El campo resultados por página debe ser un número entero.',
            'per_page.min' => 'El campo resultados por página debe tener al menos 1 caracteres/elementos.',
            'per_page.max' => 'El campo resultados por página no puede superar los 100 caracteres/elementos.',
        ];
    }
}
