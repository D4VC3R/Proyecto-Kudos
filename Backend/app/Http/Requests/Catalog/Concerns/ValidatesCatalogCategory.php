<?php

namespace App\Http\Requests\Catalog\Concerns;

use App\Catalog\CatalogProviderRegistry;
use Illuminate\Validation\Rule;

/** Reglas de la categoría compartidas por la búsqueda y la importación bajo demanda (F1-07). */
trait ValidatesCatalogCategory
{
    /**
     * Slugs de las categorías con proveedor configurado.
     *
     * @return list<string>
     */
    protected function catalogCategorySlugs(): array
    {
        $registry = app(CatalogProviderRegistry::class);

        return array_values(array_filter($registry->categorySlugs(), fn (string $slug) => $registry->has($slug)));
    }

    /** La categoría existe, está activa y tiene proveedor. */
    protected function catalogCategoryRules(): array
    {
        return ['required', 'string', Rule::in($this->catalogCategorySlugs()), Rule::exists('categories', 'slug')->where('is_active', true)];
    }
}
