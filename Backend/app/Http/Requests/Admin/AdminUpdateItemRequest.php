<?php

namespace App\Http\Requests\Admin;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Gestiona y valida las peticiones de actualización de ítems por parte de administradores.
 */
class AdminUpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('item')) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'min:20', 'max:2000'],
            'category_id' => ['sometimes', 'required', 'uuid', 'exists:categories,id'],
            'status' => ['sometimes', 'string', 'in:' . Item::STATUS_ACTIVE . ',' . Item::STATUS_INACTIVE],
            'moderation_reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
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
            'category_id.required' => 'El campo categoría es obligatorio.',
            'category_id.uuid' => 'El campo categoría debe ser un identificador válido.',
            'category_id.exists' => 'El categoría seleccionado no es válido o no existe.',
            'status.string' => 'El campo estado debe ser texto.',
            'status.in' => 'El valor seleccionado para estado no es válido.',
            'moderation_reason.string' => 'El campo motivo de moderación debe ser texto.',
            'moderation_reason.max' => 'El campo motivo de moderación no puede superar los 1000 caracteres/elementos.',
        ];
    }
}
