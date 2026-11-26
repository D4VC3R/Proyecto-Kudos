<?php

namespace App\Http\Requests\Items;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ShowItemRequest extends FormRequest
{
    /** Verifica con ItemPolicy::view() que el usuario (o el invitado) pueda ver el ítem.
     * - El usuario lo resuelve el middleware auth.optional (guard sanctum), también en esta ruta pública.
     * - Devuelve la respuesta de la policy (inspect) y no un booleano para conservar su estado:
     *   403 en un ítem inactivo y 404 en uno de una categoría inactiva.
     */
    public function authorize(): Response
    {
        return Gate::inspect('view', $this->route('item'));
    }

    public function rules(): array
    {
        return [];
    }
}
