<?php

namespace App\Http\Requests\Proposals;

use App\Models\Proposal;
use App\Rules\FileOrUrlRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Gestiona y valida las peticiones de creación de propuestas por parte de usuarios.
 */
class StoreProposalRequest extends FormRequest
{
    /** Verifica que el usuario tenga permiso para crear propuestas.
     * - El permiso se verifica utilizando la política de autorización 'create' para el modelo de propuesta.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Proposal::class);
    }

    /** Define las reglas de validación para los campos de la propuesta.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
            'image_path' => ['nullable', new FileOrUrlRule()],
            'category_id' => ['required', 'uuid', 'exists:categories,id'],
        ];
    }

}
