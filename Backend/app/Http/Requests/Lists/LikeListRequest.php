<?php

namespace App\Http\Requests\Lists;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida dar o quitar "me gusta" a una lista (F3-01). La autorización la hace el controlador
 * para poder distinguir el 404 (privada ajena) del 403 (lista propia).
 */
class LikeListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
