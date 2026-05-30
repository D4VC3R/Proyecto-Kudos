<?php

namespace App\Http\Requests\Items;

use App\Models\ItemComment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Gestiona y valida las peticiones de listado de comentarios de ítems por parte de usuarios.
 */
class ListItemCommentsRequest extends FormRequest
{
    /** Verifica que el usuario tenga permiso para ver los comentarios del ítem específico.
     * - El permiso se verifica utilizando la política de autorización 'viewAny' para el modelo de comentario, pasando el ítem como contexto.
     * - Si el usuario no tiene permiso, la petición será denegada automáticamente.
     */
    public function authorize(): bool
    {
	    $item = $this->route('item');
	    if (!$item) {
		    return false;
	    }

	    return Gate::allows('viewAny', [ItemComment::class, $item]);
    }

    /** Define las reglas de validación para listar los comentarios de un ítem.
     * - El campo 'per_page' es opcional, pero si se proporciona, debe ser un entero entre 1 y 100.
     * - Este campo se utiliza para controlar la cantidad de comentarios que se muestran por página en la paginación.
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
/**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'per_page.integer' => 'El campo resultados por página debe ser un número entero.',
            'per_page.min' => 'El campo resultados por página debe tener al menos 1 caracteres/elementos.',
            'per_page.max' => 'El campo resultados por página no puede superar los 100 caracteres/elementos.',
        ];
    }
}
