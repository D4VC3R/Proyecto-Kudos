<?php

namespace App\Http\Requests\Catalog;

use App\Catalog\CatalogProviderRegistry;
use App\Catalog\Contracts\RecognizesExternalIds;
use App\Http\Requests\Catalog\Concerns\ValidatesCatalogCategory;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la importación bajo demanda de un título del catálogo externo (F1-07).
 */
class ImportCatalogItemRequest extends FormRequest
{
    use ValidatesCatalogCategory;

    /** Los middlewares de la ruta ya exigen sesión, correo verificado y usuario no baneado. */
    public function authorize(): bool
    {
        return true;
    }

    /** Define las reglas de validación de la importación.
     * - El campo 'category' es obligatorio: el slug de una categoría activa que tenga proveedor de catálogo.
     * - El campo 'external_id' es obligatorio (máximo 100 caracteres) y debe tener el formato que reconoce el proveedor de la categoría (p. ej. 'movie:603').
     */
    public function rules(): array
    {
        return [
            'category' => $this->catalogCategoryRules(),
            'external_id' => ['required', 'string', 'max:100', function (string $attribute, mixed $value, Closure $fail) {
                $category = $this->input('category');

                if (! is_string($category) || ! in_array($category, $this->catalogCategorySlugs(), true) || ! is_string($value)) {
                    return; // ya falla la regla de la categoría o la de string
                }

                $provider = app(CatalogProviderRegistry::class)->forCategory($category);

                if ($provider instanceof RecognizesExternalIds && ! $provider->recognizes($value)) {
                    $fail('Este identificador no corresponde a la categoría elegida.');
                }
            }],
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
            'external_id.required' => 'Falta el identificador del título.',
            'external_id.max' => 'El identificador no puede superar los 100 caracteres.',
        ];
    }
}
