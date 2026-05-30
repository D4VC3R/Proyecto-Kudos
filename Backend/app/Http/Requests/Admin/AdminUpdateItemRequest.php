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
            'images' => ['sometimes', 'nullable', 'array', 'max:10'],
            'images.*.path' => ['sometimes', 'required_with:images', 'string', 'max:500'],
            'images.*.disk' => ['sometimes', 'required_with:images', 'string', 'in:public'],
            'images.*.alt' => ['sometimes', 'nullable', 'string', 'max:255'],
            'images.*.order' => ['sometimes', 'nullable', 'integer', 'min:0'],
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
            'images.array' => 'El campo imágenes debe ser una lista válida.',
            'images.max' => 'El campo imágenes no puede superar los 10 caracteres/elementos.',
            'images.*.path.required_with' => 'El campo ruta de la imagen es obligatorio cuando hay :other.',
            'images.*.path.string' => 'El campo ruta de la imagen debe ser texto.',
            'images.*.path.max' => 'El campo ruta de la imagen no puede superar los 500 caracteres/elementos.',
            'images.*.disk.required_with' => 'El campo disco de la imagen es obligatorio cuando hay :other.',
            'images.*.disk.string' => 'El campo disco de la imagen debe ser texto.',
            'images.*.disk.in' => 'El valor seleccionado para disco de la imagen no es válido.',
            'images.*.alt.string' => 'El campo texto alternativo de la imagen debe ser texto.',
            'images.*.alt.max' => 'El campo texto alternativo de la imagen no puede superar los 255 caracteres/elementos.',
            'images.*.order.integer' => 'El campo orden de la imagen debe ser un número entero.',
            'images.*.order.min' => 'El campo orden de la imagen debe tener al menos 0 caracteres/elementos.',
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
