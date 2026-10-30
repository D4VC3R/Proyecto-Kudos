<?php

namespace App\Http\Requests\Catalog;

use App\Http\Requests\Catalog\Concerns\ValidatesCatalogCategory;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la búsqueda en el catálogo externo desde /buscar (F1-07).
 */
class SearchCatalogRequest extends FormRequest
{
    use ValidatesCatalogCategory;

    /** Los middlewares de la ruta ya exigen sesión, correo verificado y usuario no baneado. */
    public function authorize(): bool
    {
        return true;
    }

    /** Define las reglas de validación de la búsqueda.
     * - El campo 'category' es obligatorio: el slug de una categoría activa que tenga proveedor de catálogo.
     * - El campo 'q' es obligatorio y debe ser una cadena de entre 2 y 100 caracteres.
     */
    public function rules(): array
    {
        return [
            'category' => $this->catalogCategoryRules(),
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ];
    }

    /**
     * Mensajes de validación personalizados para las reglas.
     */
    public function messages(): array
    {
        return [
            'category.required' => 'Elige una categoría.',
            'category.in' => 'Esta categoría no admite añadir títulos desde el catálogo externo.',
            'category.exists' => 'Esta categoría no admite añadir títulos desde el catálogo externo.',
            'q.required' => 'Escribe qué quieres buscar.',
            'q.min' => 'La búsqueda debe tener al menos 2 caracteres.',
            'q.max' => 'La búsqueda no puede superar los 100 caracteres.',
        ];
    }
}
