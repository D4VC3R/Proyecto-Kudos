<?php

namespace App\Catalog\Contracts;

/**
 * Proveedor que sabe, sin hacer peticiones, si un external_id es suyo. Sirve para traducir
 * (proveedor, external_id) a una categoría cuando varias categorías comparten la clave del proveedor
 * (películas y series comparten 'tmdb'). Un proveedor que no la implementa acepta cualquier external_id.
 */
interface RecognizesExternalIds
{
    public function recognizes(string $externalId): bool;
}
