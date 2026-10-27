<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rechaza los slugs reservados por las rutas de primer nivel del frontend (config/catalog.php → reserved_slugs).
 * No distingue mayúsculas: React Router tampoco las distingue, así que "Buscar" también taparía /buscar.
 */
class NotReservedSlug implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }

        $reserved = array_map('mb_strtolower', config('catalog.reserved_slugs', []));

        if (in_array(mb_strtolower($value), $reserved, true)) {
            $fail('Ese slug está reservado para una página de la web; elige otro.');
        }
    }
}
