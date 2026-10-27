<?php

namespace App\Http\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ShowItemRequest extends FormRequest
{
    /** Verifica que el usuario (o el invitado) tenga permiso para ver el ítem.
     * - Siempre se evalúa ItemPolicy::view(), también para los invitados: los ítems inactivos solo los ven los admin.
     * - Se usa el guard 'sanctum' de forma explícita: la ruta es pública (sin middleware auth:sanctum)
     *   y el guard por defecto ('web') no resuelve el token Bearer del frontend.
     * - Si no tiene permiso, la petición se deniega con un 403.
     */
    public function authorize(): bool
    {
        return Gate::forUser($this->user('sanctum'))->allows('view', $this->route('item'));
    }

    public function rules(): array
    {
        return [];
    }
}


