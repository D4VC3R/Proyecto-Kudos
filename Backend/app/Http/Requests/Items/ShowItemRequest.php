<?php

namespace App\Http\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;

class ShowItemRequest extends FormRequest
{
    /** Verifica que el usuario tenga permiso para ver el ítem específico.
     * - El permiso se verifica utilizando la política de autorización 'view' para el modelo de ítem.
     * - Si el usuario no tiene permiso, la petición será denegada automáticamente.
     * - Si no hay un usuario autenticado, se permite el acceso por defecto.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->route('item')) ?? true;
    }

    public function rules(): array
    {
        return [];
    }
}


