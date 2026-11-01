<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rechaza los nombres de usuario reservados (F3-03): config('kudos.profiles.reserved_usernames') y los slugs de las
 * rutas de primer nivel (config('catalog.reserved_slugs')). No distingue mayúsculas.
 */
class NotReservedUsername implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $reserved = array_map('mb_strtolower', array_merge(
            config('kudos.profiles.reserved_usernames', []),
            config('catalog.reserved_slugs', []),
        ));

        if (in_array(mb_strtolower($value), $reserved, true)) {
            $fail('Ese nombre de usuario está reservado; elige otro.');
        }
    }
}
