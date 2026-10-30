<?php

namespace App\Catalog\Data;

use App\Models\Item;

/** Resultado de la importación bajo demanda (F1-07); el controlador lo traduce a 201, 200, 409 o 404. */
final readonly class OnDemandImportResult
{
    public const CREATED = 'created';         // 201

    public const EXISTING = 'existing';       // 200

    public const UNAVAILABLE = 'unavailable'; // 409: existe pero inactivo o borrado

    public const NOT_FOUND = 'not_found';     // 404: la fuente no lo tiene

    public function __construct(public string $outcome, public ?Item $item = null) {}
}
