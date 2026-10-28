<?php

namespace App\Catalog;

use App\Catalog\Contracts\CatalogProvider;
use App\Catalog\Contracts\RecognizesExternalIds;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Resuelve el proveedor de cada categoría según config('catalog.categories').
 */
final class CatalogProviderRegistry
{
    public function __construct(private readonly Container $container) {}

    /** @return list<string> slugs de las categorías con proveedor configurado */
    public function categorySlugs(): array
    {
        return array_keys((array) config('catalog.categories', []));
    }

    public function has(string $categorySlug): bool
    {
        return filled(config("catalog.categories.{$categorySlug}.provider"));
    }

    /**
     * @throws InvalidArgumentException si la categoría no tiene proveedor configurado
     * @throws UnexpectedValueException si la clase configurada no implementa CatalogProvider
     */
    public function forCategory(string $categorySlug): CatalogProvider
    {
        $class = config("catalog.categories.{$categorySlug}.provider");

        if (blank($class)) {
            throw new InvalidArgumentException("La categoría '{$categorySlug}' no tiene proveedor de catálogo configurado.");
        }

        $provider = $this->container->make($class);

        if (! $provider instanceof CatalogProvider) {
            throw new UnexpectedValueException("El proveedor de '{$categorySlug}' (".$class.') no implementa CatalogProvider.');
        }

        return $provider;
    }

    /**
     * Categorías cuyo proveedor tiene la clave $providerKey y reconoce $externalId (catalog:import).
     * Con 'tmdb', 'movie:603' da ['peliculas'] y 'tv:1399' da ['series'].
     *
     * @return list<string>
     */
    public function categoriesFor(string $providerKey, string $externalId): array
    {
        $slugs = [];

        foreach ($this->categorySlugs() as $slug) {
            if (! $this->has($slug)) {
                continue;
            }

            $provider = $this->forCategory($slug);

            if ($provider->key() !== $providerKey) {
                continue;
            }

            if ($provider instanceof RecognizesExternalIds && ! $provider->recognizes($externalId)) {
                continue;
            }

            $slugs[] = $slug;
        }

        return $slugs;
    }
}
