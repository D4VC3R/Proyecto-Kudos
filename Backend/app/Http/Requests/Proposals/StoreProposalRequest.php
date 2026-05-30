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

/**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El campo nombre es obligatorio.',
            'name.string' => 'El campo nombre debe ser texto.',
            'name.max' => 'El campo nombre no puede superar los 255 caracteres/elementos.',
            'description.required' => 'El campo descripción es obligatorio.',
            'description.string' => 'El campo descripción debe ser texto.',
            'description.min' => 'El campo descripción debe tener al menos 20 caracteres/elementos.',
            'description.max' => 'El campo descripción no puede superar los 2000 caracteres/elementos.',
            'image_path.App\Rules\FileOrUrlRule' => 'El archivo proporcionado no es válido.',
            'category_id.required' => 'El campo categoría es obligatorio.',
            'category_id.uuid' => 'El campo categoría debe ser un identificador válido.',
            'category_id.exists' => 'El categoría seleccionado no es válido o no existe.',
        ];
    }
}
