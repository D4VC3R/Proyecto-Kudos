<?php

namespace App\Catalog\Contracts;

use App\Catalog\Data\CatalogItemData;

/**
 * Fuente externa de una categoría del catálogo (TMDB, Wikidata…). Solo se usa desde jobs y comandos,
 * nunca al servir una página (fuentes-externas §1.2).
 */
interface CatalogProvider
{
    /** Clave tal como se guarda en item_sources.provider (p. ej. 'tmdb'). */
    public function key(): string;

    /**
     * Candidatos para la importación bajo demanda (~10 como máximo; pueden ser parciales).
     *
     * @return list<CatalogItemData>
     */
    public function search(string $query): array;

    /** Ficha completa en español. null si la fuente devuelve 404. */
    public function fetch(string $externalId): ?CatalogItemData;

    /**
     * Novedades y populares para la importación periódica.
     *
     * @return iterable<CatalogItemData>
     */
    public function fetchNew(int $limit): iterable;
}
