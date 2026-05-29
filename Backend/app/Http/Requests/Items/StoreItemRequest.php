<?php

namespace App\Http\Requests\Items;

use App\Models\Item;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Gestiona y valida las peticiones de creación de ítems por parte de usuarios.
 */
class StoreItemRequest extends FormRequest
{

    /** Verifica que el usuario tenga permiso para crear un nuevo ítem.
     * - El permiso se verifica utilizando la política de autorización 'create' para el modelo de ítem.
     * - Si el usuario no tiene permiso, la petición será denegada automáticamente.
     * - Si no hay un usuario autenticado, se deniega el acceso por defecto.
     */
	public function authorize(): bool
	{
		return $this->user()?->can('create', Item::class) ?? false;
	}


    /**
     * Define las reglas de validación para crear un nuevo ítem.
     */
	public function rules(): array
	{
		return [
			'name' => ['required', 'string', 'max:255'],
			'description' => ['required', 'string', 'min:20', 'max:2000'],
			'images' => ['nullable', 'array', 'max:10'],
			'images.*.path' => ['required', 'string', 'max:500'],
			'images.*.disk' => ['required', 'string', 'in:public'],
			'images.*.alt' => ['nullable', 'string', 'max:255'],
			'images.*.order' => ['nullable', 'integer', 'min:0'],
			'category_id' => ['required', 'uuid', 'exists:categories,id'],
		];
	}


    /** Define los mensajes de error personalizados para las reglas de validación.
     * - Proporciona mensajes claros y específicos para cada regla.
     * - Estos mensajes se mostrarán cuando una validación falle la petición.
     */
	public function messages(): array
	{
		return [
			'name.required' => 'El nombre del item es obligatorio.',
			'name.max' => 'El nombre no puede exceder 255 caracteres.',
			'description.required' => 'La descripción es obligatoria.',
			'description.min' => 'La descripción debe tener al menos 20 caracteres.',
			'description.max' => 'La descripción no puede exceder 2000 caracteres.',
			'images.*.path.required' => 'La imagen es obligatoria.',
			'category_id.required' => 'Debes seleccionar una categoría.',
			'category_id.exists' => 'La categoría seleccionada no existe.',
		];
	}
}
